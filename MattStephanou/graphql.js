
//Author: Matthew Stephanou

var {GraphQLClient} = require('graphql-request');

var access_token = 'eyJlbmMiOiJBMTI4Q0JDLUhTMjU2IiwiYWxnIjoiZGlyIn0..m1EtnektfriEFo2sNzvRsA.HsqMY1dT-CdwkNrmxBFl1s6YiwW5KpoPDZ6ObpbS2yUAWD_E3_mDXzwDeYzw36_AltWLO26n5Mxels0XfideTtO2mLyvl0hdRyRP8NT1-RWJOKLzjdtQF59J43xie4qTtK3W5L8SUxTk_qZhCL-lPBejU1U2uvQVqCS2DppHuEORYjeJuUZ9UWAfDskz2DV7tS6zjBvt8UasP6ALO9ERHxzboYzqdSAYsiTHiUrfl2ZRoyD8cs_N4hkHIPBnFvb95OtOzJMebzafTP-VORtF6hrOd3O7xYP3XkeyXrH3baeJp6JxI7IppAtY9XXnhFoMnm6t9BCJgFyzfhJYcaBZqzd-vys-u8Fga1zPk_3MtoaTA4iura3VKr5BGrAPFgBUyxNpWr7zpi8ixZxp15KNUxawa2nt5j-FdKX8OO1qVNq-dbzBllYusEx5ncEEY3scHCaeXYoqUc8EiQmPvGKaemqGQUrS-NPiZa0hBa3bIftpx1EbL-MftsGo1WYnO8pz6LNhYsYrU3YfYQP0rU1H2BWTsuxomTKf8UEZNI8nyjVJRArgFdU63KEvlsTh2biOnUHhJ18dZkfEtZVa5d6WBDWH1cgkbfcnOYsBzsMCNqs3RQHueW0IJ5Xjs8i6bgbBpnQhmqaeMceEwmWUDzYRNz3xD6RmR72olgO-7zdAt4Wufts1xtti_EbVEO5LbvcS.anmVM2EdhHp3KzT8L1GyYg';

const client = new GraphQLClient('https://v4thirdparty-e2e.api.intuit.com/graphql', {
    headers: {
        Authorization: 'Bearer ' + access_token,
    },
});

const transactionsFilter = `{
    company {
        transactions(with:"txnTypeFilter='bill' && header.txnStatus='paid'") {
        edges {
            node {
            id,
                header {
                    amount
                }
            }
        }
        }
    }
}`

var first_transaction_created_id;

client.request(transactionsFilter).then(data => {
    console.log('Filter Transactions\n');
    console.log(JSON.stringify(data));

    const transactionsReadOne = `
        query getTransaction($id: ID!) {
            node(id: $id) {
                ... on Transactions_Transaction {
                header {
                    amount
                    txnDate
                }
                lines {
                    itemLines {
                            edges {
                                node {
                                    traits {
                                        item {
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
    }`

    var first_transaction_id = data.company.transactions.edges[0].node.id;
    return client.request(transactionsReadOne, {id: first_transaction_id});
})
.then(data => {
    console.log('\nRead one Transaction\n');
    console.log(JSON.stringify(data));

    const transactionsCreate = `
    mutation TransactionsCreate($transactions_create: CreateTransactions_TransactionInput!) {
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
    }`;

    var transactions_create_variables = {
        "transactions_create": {
            "clientMutationId": "8fg456dd0-88bc-4b39-9cd1-0751b7975gf6",
            "transactionsTransaction": {
                "type": "PURCHASE_BILL",
                "header": {
                    "privateMemo": "Vendor Paul C.",
                    "referenceNumber": "87y234587gh48057y",
                    "amount": "1998.00",
                    "txnDate": "2018-06-20",
                    "contact": {
                        "id": "djQuMToxMjMxNDc5MTQ1OTI3ODQ6OWQ2OTllOTYwOA:002071dc9f32b0c6c042d2a6ddc7f3981f2ce7"
                    }
                },
                "lines": {
                    "itemLines": [
                        {
                            "amount": "999.00",
                            "description": "Hardware",
                            "traits": {
                                "item": {
                                    "quantity": "1",
                                    "rate": "999.00",
                                    "item": {
                                        "id": "djQuMToxMjMxNDc5MTQ1OTI3ODQ6MTEyZGU3NDY5OQ:3"
                                    }
                                }
                            }
                        },
                        {
                            "amount": "999.00",
                            "description": "Hardware",
                            "traits": {
                                "item": {
                                    "quantity": "1",
                                    "rate": "999.00",
                                    "item": {
                                        "id": "djQuMToxMjMxNDc5MTQ1OTI3ODQ6MTEyZGU3NDY5OQ:3"
                                    }
                                }
                            }
                        }
                    ]
                }
            }
        }
    }

    return client.request(transactionsCreate, transactions_create_variables);
})
.then((data) => {
    console.log('\nCreate Transaction\n');
    console.log(JSON.stringify(data));

    first_transaction_created_id = data.createTransactions_Transaction.transactionsTransactionEdge.node.id;
    console.log(first_transaction_created_id)

    const filterAgainQuery = `{
        company {
        transactions(with:"txnTypeFilter='bill' && header.txnStatus='open' && header.contact.id='djQuMToxMjMxNDc5MTQ1OTI3ODQ6OWQ2OTllOTYwOA:002071dc9f32b0c6c042d2a6ddc7f3981f2ce7' ") {
          edges {
            node {
              id,
                header {
                        amount
                }
            }
          }
        }
      }
    }`

    return client.request(filterAgainQuery);
})
.then((data) => {
    console.log('\nFilter again\n');
    console.log(JSON.stringify(data));

    const updateTransaction = `
    mutation TransactionsUpdate($transactions_update: UpdateTransactions_TransactionInput!) {
        updateTransactions_Transaction(input: $transactions_update) {
          clientMutationId
          transactionsTransaction {
            id
            header {
              amount
              txnDate
            }
          }
        }
      }
    `;

    var updateTransactionVariables = {
        "transactions_update": {
            "clientMutationId": "8fg456dd0-88bc-4b39-9cd1-0751b7975gf6",
            "transactionsTransaction": {
                "id" : first_transaction_created_id,
                "type": "PURCHASE_BILL",
                "header": {
                    "privateMemo": "Vendor Paul C.",
                    "referenceNumber": "87y234587gh48057y",
                    "amount": "1997.00",
                    "txnDate": "2018-06-20",
                    "contact": {
                        "id": "djQuMToxMjMxNDc5MTQ1OTI3ODQ6OWQ2OTllOTYwOA:002071dc9f32b0c6c042d2a6ddc7f3981f2ce7"
                    }
                },
                "lines": {
                    "itemLines": [
                        {
                            "amount": "998.00",
                            "description": "Hardware",
                            "traits": {
                                "item": {
                                    "quantity": "1",
                                    "rate": "998.00",
                                    "item": {
                                        "id": "djQuMToxMjMxNDc5MTQ1OTI3ODQ6MTEyZGU3NDY5OQ:3"
                                    }
                                }
                            }
                        },
                        {
                            "amount": "999.00",
                            "description": "Hardware",
                            "traits": {
                                "item": {
                                    "quantity": "1",
                                    "rate": "999.00",
                                    "item": {
                                        "id": "djQuMToxMjMxNDc5MTQ1OTI3ODQ6MTEyZGU3NDY5OQ:3"
                                    }
                                }
                            }
                        }
                    ]
                }
            }
        }
    }

    return client.request(updateTransaction, updateTransactionVariables);
})
.then((data) => {
    console.log('\nUpdate Transaction\n');
    console.log(JSON.stringify(data));
})