The GraphQL server comes with an api key based auth mechanism. It uses data from the repository, linked to the user.

## Firewall setup
Add a rule for the graphql routes:

```yaml
security:
    firewalls:
        # ...

        graphql:
            pattern: /(graphql|graphiql)
            anonymous: ~
            stateless: true
            simple_preauth:
                authenticator: BD\EzPlatformGraphQLBundle\Security\ApiKeyAuthenticator
            provider: api_key_user_provider

        ezpublish_front:
            #...
```

## Creating api keys
- Create an "apikey" content type, with an "apikey" ezstring field definition.
- Change User to be a container.
- Create an apikey content below a user, with the api key string in the field.

## Use an API key
```
curl --globoff -X GET -H "apikey: my-api-key" 'https://example.com/graphql?query={viewer{login}}'
```