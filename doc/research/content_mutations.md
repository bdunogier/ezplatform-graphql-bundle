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

Update domain content mutation:
```
DomainContentMutation:
    config:
        fields:
            # @todo Generate
            updateBlogPost:
                type: BlogPostContent!
                resolve: '@=mutation("UpdateDomainContent", [args["input"], "blog_post", args["id"], args["versionNo"]])'
                args:
                    id:  { type: ID, description: "ID of the content item to update" }
                    contentId:  { type: Int, description: "ID of the content item to update" }
                    versionNo: { type: Int, description: "Optional version number to update. If it is a draft, it is saved, not published. If it is archived, it is used as the source version for the update, to complete missing fields."}
                    input: { type: BlogPostContentInput }
                    language: { type: String, defaultValue: eng-GB }
```