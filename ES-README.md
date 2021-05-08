## Elastic Search 
The initial search will be conducted on product name
with follwing query 

```
GET products/data/_search
{
  "_source": ["name", "product_url"],
  "suggest": {
    "product-suggest": {
      "prefix": "ba",
      "completion": {
        "field": "product_name"
      }
    }
  }
}
```

inital mapping includes

```
{
    "mappings": {
        "data": {
            "properties": {
                "product_name": {
                    "type" : "completion"
                },
                "name": {
                    "type": "text"
                },
                "product_sku": {
                    "type": "text"
                },
                "product_url": {
                    "type":"text"
                }
            }
        }
    }
}
```