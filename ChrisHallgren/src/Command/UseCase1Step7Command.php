<?php
namespace App\Command;

use Cake\Console\Arguments;
use Cake\Console\Command;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;

/**
 * UseCase1Step7 command.
 */
class UseCase1Step7Command extends Command
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
        $po_id = $io->ask('Purchase Order ID');
        $po_id = Configure::read('qbo_global_id') . ':' . $po_id;
        $io->out('Retrieving Purchase Order');

        $client = \Softonic\GraphQL\ClientBuilder::build('https://v4thirdparty-e2e.api.intuit.com/graphql', ['headers' => ['Authorization' => Configure::read('qbo_auth')]]);
        //TODO Query doesn't return any results
        $query = <<<'QUERY'
query UseCase1Step7Part1Command($id: ID!) {
  node(id: $id) {
    ... on Transactions_Transaction {
			type
      header {
				referenceNumber
        amount
        txnDate
				contact {
					id
				}
      }
			lines {
				itemLines {
					edges {
						node {
                            amount
							description
							traits {
								item {
                                
									rate
									quantity
									item {
										id
									}
								}
							}
						}
					}
				}
			}
    }
  }
}
QUERY;
        $response = $client->query($query, ['id' => $po_id]);
        if($response->getErrors()) {
            foreach($response->getErrors() as $error) {
                $io->hr();
                $io->out('Received ' . $error['$type']);
                $io->out('Error Code: ' . $error['code']);
                $io->out('Error Type: ' . $error['type']);
                $io->out('Error Message: ' . $error['message']);
            }
        } else {
            $this->_createBillFromPo($response->getData()['node'], $io, $client);
//            foreach ($response->getData()['company']['transactions']['edges'] as $edge) {
//                $io->hr();
//                $io->out('Returned Transaction ID: ' . $edge['node']['id']);
//                if (!is_null($edge['node']['type'])) {
//                    $io->out('Transaction Type: ' . $edge['node']['type']);
//                } else {
//                    $io->out('Unknown Transaction Type!!!');
//                }
//                $io->out('Date: ' . $edge['node']['header']['txnDate']);
//                $io->out('Status: ' . $edge['node']['header']['txnStatus']);
//                $io->out('Amount: $' . number_format($edge['node']['header']['amount'], 2));
//            }
//            $io->hr();
//            $io->out('Count: ' . count($response->getData()['company']['transactions']['edges']));
        }
    }
    
    private function _createBillFromPo($data, ConsoleIo $io, $client) {
        $data['header']['referenceNumber'] = $data['header']['referenceNumber'] . '-' . rand(1000,9999);
        $data['type'] = 'PURCHASE_BILL';
        foreach ($data['lines']['itemLines']['edges'] as $k => $v) {
            $data['lines']['itemLines'][$k] = $v['node'];
        }
        unset($data['lines']['itemLines']['edges']);
        $vars['transactions_create'] = [
            'clientMutationId' => \Cake\Utility\Text::uuid(),
            'transactionsTransaction' => $data
        ];
        debug($vars);
        $query = <<<'QUERY'
mutation UseCase1Step7Part2Command($transactions_create: CreateTransactions_TransactionInput!) {
  createTransactions_Transaction(input: $transactions_create) {
    clientMutationId
    transactionsTransactionEdge {
    node {
      id
      header {
        amount
        txnDate
        txnStatus
        referenceNumber
      }
      }
    }
  }
}
QUERY;
        $response = $client->query($query, $vars);
        if($response->getErrors()) {
            foreach($response->getErrors() as $error) {
                debug($error);
                $io->hr();
                $io->out('Received ' . $error['$type']);
                if (isset($error['code'])) {
                    $io->out('Error Code: ' . $error['code']);
                }
                $io->out('Error Type: ' . $error['type']);
                $io->out('Error Message: ' . $error['message']);
            }
        } else {
            debug($response->getData());
//            foreach ($response->getData()['company']['transactions']['edges'] as $edge) {
//                $io->hr();
//                $io->out('Returned Transaction ID: ' . $edge['node']['id']);
//                if (!is_null($edge['node']['type'])) {
//                    $io->out('Transaction Type: ' . $edge['node']['type']);
//                } else {
//                    $io->out('Unknown Transaction Type!!!');
//                }
//                $io->out('Date: ' . $edge['node']['header']['txnDate']);
//                $io->out('Status: ' . $edge['node']['header']['txnStatus']);
//                $io->out('Amount: $' . number_format($edge['node']['header']['amount'], 2));
//            }
//            $io->hr();
//            $io->out('Count: ' . count($response->getData()['company']['transactions']['edges']));
        }
    }
}
