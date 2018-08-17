<?php
namespace App\Command;

use Cake\Console\Arguments;
use Cake\Console\Command;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;

/**
 * UseCase1Step1 command.
 */
class UseCase1Step1Command extends Command
{

    /**
     * Hook method for defining this command's option parser.
     *
     * @see https://book.cakephp.org/3.0/en/console-and-shells/commands.html#defining-arguments-and-options
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    public function buildOptionParser(ConsoleOptionParser $parser)
    {
        $parser = parent::buildOptionParser($parser);

        return $parser;
    }

    /**
     * Implement this method with your command's logic.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return null|int The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        $io->out('Reading All Bills with Status PAID');

        $client = \Softonic\GraphQL\ClientBuilder::build('https://v4thirdparty-e2e.api.intuit.com/graphql', ['headers' => ['Authorization' => Configure::read('qbo_auth')]]);
        //TODO Query doesn't return any results
        $query = <<<'QUERY'
query UseCase1Step1Command {
  company {
    transactions(with: "txnTypeFilter='bill' && status='paid'") {
      edges {
        node {
          id
          type
          header {
            txnStatus
            contact {
							id
            }
						txnDate
						amount
						privateMemo
          }
          externalIds {
            localId
          }
        }
      }
    }
  }
}
QUERY;
        $response = $client->query($query);
        if($response->getErrors()) {
            foreach($response->getErrors() as $error) {
                $io->hr();
                $io->out('Received ' . $error['$type']);
                $io->out('Error Code: ' . $error['code']);
                $io->out('Error Type: ' . $error['type']);
                $io->out('Error Message: ' . $error['message']);
            }
        } else {
            foreach ($response->getData()['company']['transactions']['edges'] as $edge) {
                $io->hr();
                $io->out('Returned Transaction ID: ' . $edge['node']['id']);
                if (!is_null($edge['node']['type'])) {
                    $io->out('Transaction Type: ' . $edge['node']['type']);
                } else {
                    $io->out('Unknown Transaction Type!!!');
                }
                $io->out('Date: ' . $edge['node']['header']['txnDate']);
                $io->out('Status: ' . $edge['node']['header']['txnStatus']);
                $io->out('Amount: $' . number_format($edge['node']['header']['amount'], 2));
            }
            $io->hr();
            $io->out('Count: ' . count($response->getData()['company']['transactions']['edges']));
        }
    }
}
