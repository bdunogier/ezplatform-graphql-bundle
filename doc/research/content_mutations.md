Crude schema that allows to create a blog post:

```
DomainContentMutation:
    type: object
    config:
        fields:
            createBlogPost:
                type: BlogPostContent!
                resolve: "@=mutation('CreateDomainContent', [args['input'], 'blog_post'])"
                args:
                    input:
                        type: BlogPostContentInput!

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
            #author:
            #    type: '[AuthorFieldValue]'
            #    resolve: '@=resolver("DomainFieldValue", [value, "author"]).authors'
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