package com.cloudbusiness.graphql

import com.cloudbusiness.auth.SocialToken
import com.cloudbusiness.entity.intuit.IntuitEntity
import grails.converters.JSON
import groovyx.net.http.ContentType
import groovyx.net.http.HTTPBuilder
import groovyx.net.http.Method
import org.apache.commons.lang.RandomStringUtils

/**
 *  Bad code that just do simple call to V4 API.
 *
 * @author Michael Astreiko
 */
class GraphQLService {
    
    def processWithGraphql(SocialToken socialToken, IntuitEntity intuitEntity) {
        //V3 Entity
        def jsonBody = JSON.parse(intuitEntity.toJsonString())
        def mutationId = RandomStringUtils.randomAlphanumeric(16)
        log.error "mutationId: $mutationId"
        //Mapping V3 <-> V4
        def variables = """{
  "transactions_create": {
    "clientMutationId": "$mutationId",
    "transactionsTransaction": {
      "type": "PURCHASE_BILL",
      "header": {
        "privateMemo": "${jsonBody['PrivateNote']}",
        "referenceNumber": "${jsonBody['DocNumber']}",
        "amount": "${jsonBody['TotalAmt']}",
        "txnDate": "${jsonBody['TxnDate']}",
        "contact": {
          "id": "${jsonBody['CustomerRef'].value}"
        }
      },
      "lines": {
        "itemLines": ["""
        jsonBody["Line"].each { line ->
            variables += """{
            "amount": "${line['Amount']}",
            "description": "${line['Description']}",
            "traits": {
              "item": {
               "quantity": "1",
                "item": {
                  "id": "djQuMToxMjMxNDc1MDc5NzI4MTQ6MTEyZGU3NDY5OQ:${line['SalesItemLineDetail'].ItemRef.value}"
                }
              }
            }
            }"""
        }


        variables += """]
      }
    }
  }
}
"""
        def query = """{"query":"mutation TransactionsCreate(\$transactions_create: CreateTransactions_TransactionInput!) {\\n  createTransactions_Transaction(input: \$transactions_create) {\\n    clientMutationId\\n    transactionsTransactionEdge {\\n      node {\\n        id\\n        header {\\n          amount\\n          txnDate\\n        }\\n      }\\n    }\\n  }\\n}\\n","variables":${variables.toString()},"operationName":"TransactionsCreate"}"""

        def result = [:]
        new HTTPBuilder("https://v4thirdparty-e2e.api.intuit.com/graphql").request(Method.POST, ContentType.JSON) { req ->
            headers.'Authorization' = "Bearer ${socialToken.token}"
            headers.'intuit_tid' = mutationId
            body = query
            response.success = { resp, json ->
                result.data = json
            }

            response.failure = { resp ->
                log.info 'request details: ' + req.properties
                log.info 'request failed: ' + resp.properties
            }

        }
        result

    }
}
