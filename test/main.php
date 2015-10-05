import 'package:susi/susi.dart';
import 'package:args/args.dart';

main(List<String> arguments) {
  final parser = new ArgParser()
    ..addOption('host', defaultsTo: 'localhost', abbr: 'h')
    ..addOption('port', defaultsTo: 4000, abbr: 'p')
    ..addOption('key', defaultsTo: 'key.pem', abbr: 'k')
    ..addOption('cert', defaultsTo: 'cert.pem', abbr: 'c');

  ArgResults argResults = parser.parse(arguments);

  var susi = new Susi(argResults['host'], argResults['port'], argResults['key'], argResults['cert']);

  susi.connect().then((_) {
    susi.registerProcessor('.*', (event) {
      event['payload'] = {'p1': true};
      print('p1: ${event["payload"]}');
      susi.ack(event);
    });

    susi.registerProcessor('foobar', (event) {
      event['payload']['p2'] = true;
      print('p2: ${event["payload"]}');
      susi.dismiss(event);
    });

    susi.registerProcessor('foobar', (event) {
      event['payload']['p3'] = true;
      print('p3: ${event["payload"]}');
      susi.ack(event);
    });

    susi.registerConsumer('foobar', (event) {
      print('consumer: ${event["payload"]}');
    });

    susi.publish({'topic': 'foobar'})
        .then((event) => print('got ack: ${event["payload"]}'))
        .catchError((event) => print('got dismiss: ${event["payload"]}'))
        .whenComplete(() => print('finally got ready'));
  }).catchError((err) => print(err));
}
