Crude schema that allows to create a blog post:

```yaml
# src/AppBundle/Resources/config/graphql/DomainContentMutation.types.yml:
DomainContentMutation:
    type: object
    config:
        fields:
            createBlogPost:
                type: BlogPostContent!
                resolve: "@=mutation('CreateDomainContent', [args['input'], 'blog_post', args['parentLocationId'], args['language']])"
                # @todo use args builder ?
                args:
                    input:
                        type: BlogPostContentInput!
                    language:
                        type: String
                        defaultValue: "eng-GB"
                    parentLocationId:
                        type: Int!

BlogPostContentPayload:
    type: object
    config:
        fields:
            item:
                type: BlogPostContent

BlogPostContentInput:
    type: input-object
    config:
        fields:
            title:
                type: String!
            intro:
                type: String
            body:
                type: String
            #image:
            #    type:
            #publicationDate:
            #    type: GenericFieldValue
            #    resolve: '@=resolver("DomainFieldValue", [value, "publication_date"])'
            author:
                type: '[AuthorInput]'
            #    resolve: '@=resolver("AuthorFieldInput", [value])'
            #authorsPosition:
            #    type: String
            #    resolve: '@=resolver("DomainFieldValue", [value, "authors_position"])'
            #tags:
            #    type: GenericFieldValue
            #    resolve: '@=resolver("DomainFieldValue", [value, "tags"])'
            #metas:
            #    type: GenericFieldValue
            #    resolve: '@=resolver("DomainFieldValue", [value, "metas"])'
```