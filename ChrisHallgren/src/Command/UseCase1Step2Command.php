<?php
namespace App\Command;

use Cake\Console\Arguments;
use Cake\Console\Command;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;

/**
 * UseCase1Step2 command.
 */
class UseCase1Step2Command extends Command
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
        $io->out('Creating Bill');

        $client = \Softonic\GraphQL\ClientBuilder::build('https://v4thirdparty-e2e.api.intuit.com/graphql', ['headers' => ['Authorization' => Configure::read('qbo_auth')]]);
        $query = <<<'QUERY'
mutation UseCase1Step2Command($transactions_create: CreateTransactions_TransactionInput!) {
  createTransactions_Transaction(input: $transactions_create) {
    clientMutationId
    transactionsTransactionEdge {
      node {
        id
        header {
          txnDate
        }
      }
    }
  }
}
QUERY;
        //TODO Would assume that the description would be populated
        $items[] = [
            'description' => 'Line 1',
            'traits' => [
                'item' => [
                    'rate' => rand(1, 50) . '.' . str_pad(rand(0, 99), 2, '0', STR_PAD_LEFT),
                    'quantity' => rand(1, 100),
                    'item' => [
                        'id' => '3',
                    ]
                ]
            ]
        ];
        $items[] = [
            'description' => 'Line 2',
            'traits' => [
                'item' => [
                    'rate' => rand(1, 50) . '.' . str_pad(rand(0, 99), 2, '0', STR_PAD_LEFT),
                    'quantity' => rand(1, 100),
                    'item' => [
                        'id' => '4',
                    ]
                ]
            ]
        ];
        //TODO Would assume that the amounts would be calculated.
        foreach ($items as $k => $v) {
            $items[$k]['amount'] = number_format(($v['traits']['item']['quantity'] * $v['traits']['item']['rate']), 4, '.', '');
        }
        $vars['transactions_create'] = [
            'clientMutationId' => \Cake\Utility\Text::uuid(),
            'transactionsTransaction' => [
                'type' => 'PURCHASE_BILL',
                'header' => [
                    'referenceNumber' => rand(1000, 9999),
                    'txnDate' => date('Y-m-d'),
                    'contact' => [
                        'id' => '1'
                    ]
                ],
                'lines' => [
                    'itemLines' => $items
                ]
            ]
        ];
        $response = $client->query($query, $vars);
        if ($response->getErrors()) {
            foreach ($response->getErrors() as $error) {
                $io->hr();
                $io->out('Received ' . $error['$type']);
                if (isset($error['code'])) {
                    $io->out('Error Code: ' . $error['code']);
                }
                $io->out('Error Type: ' . $error['type']);
                $io->out('Error Message: ' . $error['message']);
            }
        } else {
            $data = $response->getData()['createTransactions_Transaction']['transactionsTransactionEdge']['node'];
            $io->hr();
            $io->out('Created Bill With ID: ' . $data['id']);
            $io->out('Date: ' . $data['header']['txnDate']);
        }
    }
}
