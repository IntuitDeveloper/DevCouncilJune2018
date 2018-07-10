# api call

- Transaction ReadAll (bills)
- Transaction Create (bill)

# Overview

OneSaas integrations (Spokes) consist of json files that are executed by sync engines (the Hub). 

```spoke.json``` defines the name, category and some ids for the Spoke.

```connection.dev.json``` contains all the details about how to connect to the QBO staging API

Each entity (```bill```, invoice, contact, item, ...) is represented by 2 json files, one that will transform the data and one that will communicate with the target API.

```bills.json``` is responsible for talking to the GraphQL endpoint.

``` bills.transform.json``` is responsible for transforming bills when sending and receiving.

**Scenario 1**: Read all the bills from QBO

1. Start with ```bills.json > sytem:retrieve```
    ```
    This is the "data layer". 
    
    An http post is made to the GraphQL endpoint. 
    
    The GraphQL query itself is read from a file using @.Gql()
    ```
1. Each bill retrieved from the API will then be transformed. Check out ```bills.transform.json > system:retrieve```
```
This is used to re-shape the QBO data into the normalized OneSaas schema. 

Once transformed, the data is saved into OneSaas and ready to flow onwards to other systems.
```

**Scenario 2**: Create a new bill in QBO

1. Start with ```bills.transform.json > system:create```
    ```
    The data is already in OneSaas.

    This step will re-shape it into what the QBO v4 mutation requires.
    ```
2. Continue with ```bills.json > system:create```
```
Now that the mutation is ready, we can perform the call to the GraphQL endpoint.
```



