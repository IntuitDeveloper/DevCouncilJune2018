
	public function qbodebug_for_customer($Router, $Request, $Response)
	{
		header('Content-Type: text/plain');

		$our_customer_id = $Request->_('tenant_id');


		$retr = _do_graphql(file_get_contents(_DIR_GRAPHQL_ . '/BillsForVendorquery.graphql'));

		print_r($retr);

	}