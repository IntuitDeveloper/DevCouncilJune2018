<?php

...


define('_DIR_GRAPHQL_', dirname(__FILE__) . '/../graphql/');

function saas_event__qbov4($context_str, $context_id, $event_str, $action_data, $event_data, $blip_data, &$err)
{
	$API = $blip_data['API'];

	$InvoiceModel = $API->model('saas', 'invoice');
	$QuickBooksModel = $API->model('saas', 'quickbooks');

	if (($event_str == 'insert' or $event_str == 'update') and
		strtolower($context_str) == 'model_saas_invoice')
	{
		$lines = array();
		$invoice = $InvoiceModel->load($context_id, $lines);

		//error_log(print_r($invoice, true));

		$qbo_id = $QuickBooksModel->lookup($InvoiceModel->context(), $context_id);

		if ($qbo_id)
		{
			error_log('UPDATE');
			$retr = _qbov4_update_bill($qbo_id, $invoice, $lines);
		}
		else
		{
			error_log('CREATE');
			$retr = _qbov4_create_bill($invoice, $lines);
		}

		$qbo_id = $retr['data']['createTransactions_Transaction']['transactionsTransactionEdge']['node']['id'];

		error_log(print_r($retr, true));

		$QuickBooksModel->map($InvoiceModel->context(), $context_id, $qbo_id);
	}

}

/*
function my_array_function(&$k, $v)
{
	if (is_object($v))
	{
		$k = $v->toArray();
	}


}
*/


class QBOv4_Object
{
	public $_data = array();

	public function __construct()
	{
		$this->_data = array();
	}

	public function toArray()
	{
		return $this->_data;
	}

	/*
	public function walk($callback)
	{
		//print_r($this->_data);
		//exit;

		foreach ($this->_data as $key => $value)
		{
			if (is_object($value))
			{
				$value->walk($callback);
			}
			else if (is_array($value))
			{
				foreach ($value as $skey => $svalue)
				{
					if (is_object($svalue))
					{
						$svalue->walk($callback);
					}
					else
					{
						$this->_data[$key][$skey] = call_user_func($callback, $key, $svalue);
					}
				}
			}
			else
			{
				$this->_data[$key] = call_user_func($callback, $key, $value);
			}
		}
	}
	*/

	public function toJSON()
	{
		// First make sure everything is an array
		/*
		array_walk_recursive($this->_data, function (&$value) {
		        if (is_object($value)) {
		            $value = $value->toArray();
		        }
		    });
		*/

		return json_encode(
			array(
				'transactions_create' => array(
        			'clientMutationId' => md5(mt_rand()),
					'transactionsTransaction' => $this->_data
				)), JSON_PRETTY_PRINT);
	}

	public function __call($name, $args)
	{
		if (substr($name, 0, 3) == 'set')
		{
			//print('called: ' . $name . ' with args: ' . print_r($args, true) . "\n");

			$field = substr($name, 3);

			$tmp = null;
			if (count($args) == 1)
			{
				$tmp = current($args);
				$this->_data[lcfirst($field)] = $tmp;
			}
			else
			{

			}

			return $tmp;
		}
		else if (substr($name, 0, 3) == 'get')
		{
			$field = substr($name, 3);

			//print('getting field: [' . $field . ']' . "\n");
			//print_r($this->_data);


			if (isset($this->_data[$field]))
			{
				if (isset($args[0]) and
					is_numeric($args[0]))
				{
					// Trying to fetch a repeating element
					if (isset($this->_data[$field][$args[0]]))
					{
						return $this->_data[$field][$args[0]];
					}

					return null;
				}
				else if (!count($args) and
					isset($this->_data[$field]) and
					is_array($this->_data[$field]))
				{
					return $this->_data[$field][0];
				}
				else
				{
					// Normal data
					return $this->_data[$field];
				}
			}

			return null;
		}
		else if (substr($name, 0, 5) == 'count')
		{
			$field = substr($name, 5);

			if (isset($this->_data[$field]) and
				is_array($this->_data[$field]))
			{
				return count($this->_data[$field]);
			}
			else if (isset($this->_data[$field]))
			{
				return 1;
			}
			else
			{
				return 0;
			}
		}
		else if (substr($name, 0, 3) == 'add')
		{
			$field = substr($name, 3);

			if (!isset($this->_data[$field]))
			{
				$this->_data[$field] = array();
			}

			$tmp = current($args);
			$this->_data[$field][] = $tmp;

			return $tmp;
		}
		else if (substr($name, 0, 5) == 'unset')
		{
			$field = substr($name, 5);

			if (isset($this->_data[$field]))
			{
				if (isset($args[0]) and
					is_numeric($args[0]))
				{
					// Trying to fetch a repeating element
					if (isset($this->_data[$field][$args[0]]))
					{
						unset($this->_data[$field][$args[0]]);
					}

					return true;
				}
				else
				{
					unset($this->_data[$field]);
				}
			}
		}
		else
		{
			trigger_error('Call to undefined method $' . get_class($this) . '->' . $name . '(...)', E_USER_ERROR);
			return false;
		}
	}
}


class QBOv4_Bill extends QBOv4_Object
{

}


class QBOv4_Line extends QBOv4_Object
{

}

function _qbov4_update_bill($qbo_id, $invoice, $lines)
{
	$b = new QBOv4_Bill();

	$b->setId($qbo_id);

	$b->setType('PURCHASE_BILL');
	$b->setHeader(array(
					"privateMemo" => "Vendor rep: Paul C.",
					"referenceNumber" => $invoice['refnumber'],
					//"amount" => $invoice['total'],
					"txnDate" => $invoice['invoice_date'],
					"contact" => array(
						"id" => "1"
					)));



	$b_lines = array();
	foreach ($lines as $line)
	{
		/*
		$b = new QBOv4_Line();

		$b->setAmount($line['line_total']);
		$b->setDescription($line['descrip']);

		$b->setTraits(array(
				"item" => array(
					"quantity" => $line['line_quantity'],
					"rate" => $line['line_rate'],
					"item" => array(
						"id" => "djQuMToxMjMxNDc1MDc5NzI4MTQ6MTEyZGU3NDY5OQ:3"
					)
				)
			));

		$b_lines[] = $b;
		*/

		$b_lines[] = array(
			'amount' => $line['line_total'],
			'description' => $line['descrip'],
			'traits' => array(
				"item" => array(
					"quantity" => $line['line_quantity'],
					"rate" => $line['line_rate'],
					"item" => array(
						"id" => "djQuMToxMjMxNDc1MDc5NzI4MTQ6MTEyZGU3NDY5OQ:3"
					)
				)
			));
	}

	$b->setLines(array( 'itemLines' => $b_lines ) );

	/*
	$query = 'mutation TransactionsCreate($transactions_create: CreateTransactions_TransactionInput!) {
  createTransactions_Transaction(input: $transactions_create) {
    clientMutationId
    transactionsTransactionEdge {
      node {
        id
				header{
					amount
					txnDate
				}
      }
    }
  }
}
';*/

	$query = file_get_contents(_DIR_GRAPHQL_ . '/CreateBill.graphql');

	return _do_graphql($query, $b);
}

function _qbov4_create_bill($invoice, $lines)
{
	/*
	$arr = array(
		"transactions_create" => array(
			"clientMutationId" => md5(mt_rand()),
			"transactionsTransaction" => array(
				"type" => "PURCHASE_BILL",
				"header" => array(
					"privateMemo" => "Vendor rep: Paul C.",
					"referenceNumber" => $invoice['refnumber'],
					//"amount" => $invoice['total'],
					"txnDate" => $invoice['invoice_date'],
					"contact" => array(
						"id" => "1"
					)
				),
				"lines" => array(
					"itemLines" => array(

					)
				)
			)
		));
	*/

	$b = new QBOv4_Bill();

	$b->setType('PURCHASE_BILL');
	$b->setHeader(array(
					"privateMemo" => "Vendor rep: Paul C.",
					"referenceNumber" => $invoice['refnumber'],
					//"amount" => $invoice['total'],
					"txnDate" => $invoice['invoice_date'],
					"contact" => array(
						"id" => "1"
					)));



	$b_lines = array();
	foreach ($lines as $line)
	{
		/*
		$b = new QBOv4_Line();

		$b->setAmount($line['line_total']);
		$b->setDescription($line['descrip']);

		$b->setTraits(array(
				"item" => array(
					"quantity" => $line['line_quantity'],
					"rate" => $line['line_rate'],
					"item" => array(
						"id" => "djQuMToxMjMxNDc1MDc5NzI4MTQ6MTEyZGU3NDY5OQ:3"
					)
				)
			));

		$b_lines[] = $b;
		*/

		$b_lines[] = array(
			'amount' => $line['line_total'],
			'description' => $line['descrip'],
			'traits' => array(
				"item" => array(
					"quantity" => $line['line_quantity'],
					"rate" => $line['line_rate'],
					"item" => array(
						"id" => "djQuMToxMjMxNDc1MDc5NzI4MTQ6MTEyZGU3NDY5OQ:3"
					)
				)
			));
	}

	$b->setLines(array( 'itemLines' => $b_lines ) );

	$query = 'mutation TransactionsCreate($transactions_create: CreateTransactions_TransactionInput!) {
  createTransactions_Transaction(input: $transactions_create) {
    clientMutationId
    transactionsTransactionEdge {
      node {
        id
				header{
					amount
					txnDate
				}
      }
    }
  }
}
';

	return _do_graphql($query, $b);
}

function _do_graphql($query, $v = null)
{
	$ch = curl_init('https://v4thirdparty-e2e.api.intuit.com/graphql');

	//$variables = json_encode($arr);



	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/json',
		'Authorization: Bearer eyJlbmMiOiJBMTI4Q0JDLUhTMjU2IiwiYWxnIjoiZGlyIn0..7bI2-rhwgLuTxnNfB3KGdA.cEHStIpEko03S4eRxgXMmiO8EqiqMsCMQvhS-w18Zqxmr-AFRyDZvtDZeh-GAM89QWx6XJ1yG2AfdM4ZuJ3I8jTPujqZ7KBEB7Xo3iieGJTtFapzYis3YdkIxLEvm7Kp_FShaAY_T1pVIxq59h_UHUNQGl9ue9-Pu-ruYlPBnnSe3FWY43d7IbhROdVmpoAtWYaFAiKD7eSG0AtPIW9B5L7HZsXTMEGQq2ksNeQ0JPFgiMRGKyx6ymABaJyarNO2ZRInfl38EqVu90H2yPTSqJ5wcOxTIyQWu1JiMtl2mAN1v9rcm5Vhuc4rZxnMU9wp4xy89Dww77Nb0k-vxuwrtabSAbeCdQ4NJb0Rz3SkzvIPrxztEuSuN4CYBQND6ppPmU301_vSiBqSHkBDL42OAkFwZhtEgTe0qh_9ulY9xdmbW1wCPWs9ZLZh56NzNUJry9IYG6-TpafbxXjhosgEK5D6AkrVF_th1qlWsVm238o8NRakBoyWH4_YihtrHMDvwNUHzUjUMSpXBBzZ2Vj2hTUzefXYDDC--bU5_vs_GBJjaiPvSLfEbHnxaNQlZP8oqe1Ii59Q_k0DoTM-bTGu2myCLsgp9kB1kozP6IQYpU7cYgVkpUjI3hT_gbbP8QZQ_QGot7FBheMOQ1WJJ_az4vQNwEEyiGCLRiIOaB-xVU9NrkZEQtJwg_WoPBQCFaA2.CPXOh9C6iiI_7sr4akex8g',
		));

	$json = json_encode(null);
	if ($v)
	{
		$json = $v->toJSON();
	}

	$thing_to_send = '{"query":' . json_encode($query) . ',"variables":' . $json . '}';

	error_log('REALLY RUN: ' . $json);

	//$thing_to_send = '{"query":"query CompanyQuery {\n  company {\n    transactions(with:\"txnTypeFilter=\'bill\' && dateRange=\'custom\' && lowDate=\'2018-06-20\' && highDate=\'2018-06-28\'\")  {\n      edges {\n        node {\n          type\n          header {\n            txnStatus\n            txnDate\n            amount\n          }\n          meta {\n            created\n            updated\n          }\n          id\n        }\n      }\n    }\n  }\n}","operationName":"CompanyQuery"}';

	//error_log('ABOUT TO RUN: ' . $thing_to_send);


	curl_setopt($ch, CURLOPT_POSTFIELDS, $thing_to_send);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);


	$retr = curl_exec($ch);

	$info = curl_getinfo($ch);

	error_log(print_r($info, true));

	error_log('RETR: ' . $retr);
	//print($retr);

	return json_decode($retr, true);
}


...

