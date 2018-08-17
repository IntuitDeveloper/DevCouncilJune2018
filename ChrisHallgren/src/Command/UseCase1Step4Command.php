<?php
namespace App\Command;

use Cake\Console\Arguments;
use Cake\Console\Command;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;

/**
 * UseCase1Step4 command.
 */
class UseCase1Step4Command extends Command
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
        $io->out('Reading All Open Bills for August and All Bills for Vendor 1');

        $client = \Softonic\GraphQL\ClientBuilder::build('https://v4thirdparty-e2e.api.intuit.com/graphql', ['headers' => ['Authorization' => Configure::read('qbo_auth')]]);
        $query = <<<'QUERY'
query UseCase1Step4Command {
  company {
    open_bills: transactions(filterBy:"type='PURCHASE_BILL' && header.txnStatus='OPEN'", with: "dateRange='custom' && lowDate='2018-08-01' && highDate='2018-08-30'") {
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
                        referenceNumber
          }
          externalIds {
            localId
          }
        }
      }
    },
    bills_for_vendor: transactions(with: "txnTypeFilter='bill'", filterBy: "header.contact.externalIds.localId='1'") {
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
                        referenceNumber
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
            $data = $response->getData()['company'];
            if(count($data['open_bills']['edges']) == 0) {
                $io->out('No Open Bills for August');
                $io->hr();
            } else {
                $io->out(count($data['open_bills']['edges']) . ' Open Bills for August');
                $io->hr();
            }
            foreach ($data['open_bills']['edges'] as $bill) {
                $bill = $bill['node'];
                $io->out($bill['header']['txnStatus'] . ' ' . $bill['type'] . ' ID: ' . $bill['id'] . ' Date: ' . $bill['header']['txnDate'] . ' Doc Number: ' . $bill['header']['referenceNumber'] . ' Amount: $' . $bill['header']['amount']);
            }
            if(count($data['bills_for_vendor']['edges']) == 0) {
                $io->out('No Bills for Vendor');
                $io->hr();
            } else {
                $io->out(count($data['bills_for_vendor']['edges']) . ' Bills for Vendor');
                $io->hr();
            }
            foreach ($data['bills_for_vendor']['edges'] as $bill) {
                $bill = $bill['node'];
                $io->out($bill['header']['txnStatus'] . ' ' . $bill['type'] . ' ID: ' . $bill['id'] . ' Date: ' . $bill['header']['txnDate'] . ' Doc Number: ' . $bill['header']['referenceNumber'] . ' Amount: $' . $bill['header']['amount']);
            }
        }
    }
}
