# The domain schema

The domain content is the main GraphQL schema of eZ Platform. It is created
based on the content model: your types and theirs fields.

Its usage requires that the GraphQL model is generated for a given repository,
as a set of configuration files in the project's AppBundle.

The generated schema exposes:
- content types groups, with their camel cased identifier: `content`, `media`...
    - a `_types` field
        - each content type is exposed using its camel cased identifier: `blogPost`, `landingPage`
            - below each content type, one field per field definition of the type, using its
              camel cased identifier: `title`, `relatedContent`.
                - for each field definition, its properties:
                    - common ones: `name`, `descriptions`, `isRequired`...
                    - type specific ones: `constraints.minLength`, `settings.selectionLimit`...
    - the content types from the group, as two fields:
        a) plural (`articles`, `blogPosts`)
        b) singular (`article`, `blogPost`)
        - for each content type, one field per field definition, returning the field's value

Queries look like this:

```
{
  content {
    articles {
      title
      body { html }
      image {
        name
        variations(alias: large) { uri }
      }
    }
    folder(id: 1234) {
      name
    }
  }
}
```

## Mutations
For each content type, two mutations will be exposed: `create{ContentType}` and `update{ContentType}`:
`createArticle`, `updateBlogPost`, ... they can be used to respectively create and update content items
of each type. In addition, an input type is created for each of those mutations: `{ContentType}ContentCreateInput`,
`{Article}ContentUpdateInput`, used to provide input data to the mutations.

### Authentication
The current user needs to be authorized to perform the operation. You can log in using `/login` to get a session cookie,
and add that session cookie to the request. With GraphiQL, logging in on another tab will work.

### Example

```
mutation CreateBlogPost {
  createBlogPost(
    parentLocationId: 2,
    input: {
      title: "The blog post's title",
      author: [
        {name: "John Doe", email: "johndoe@unknown.net"}
      ],
      body: "<?xml version=\"1.0\"?>\n<section xmlns=\"http://docbook.org/ns/docbook\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" xmlns:ezxhtml=\"http://ez.no/xmlns/ezpublish/docbook/xhtml\" xmlns:ezcustom=\"http://ez.no/xmlns/ezpublish/docbook/custom\" version=\"5.0-variant ezpublish-1.0\">\n<para>A paragraph\n</para>\n</section>"
    }
  ) {
    _info { id mainLocationId }
    title
  }
}
```


## Setting it up

Run `php bin/console bd:platform-graphql:generate-domain-schema` from the root of your
eZ Platform installation. It will go over your repository, and generate the matching
types in `app/config/graphql/`.

Open `<host>/graphiql`. The content type groups, content types and their fields
will be exposed as the schema.

## Customizing the schema

### Schema workers

Schema workers are used by the Repository Domain Generator to generate the domain's schema.
The generator iterates on objects from the repository, and passes on the loaded data and the schema.

Example:

The `DefineDomainContent` worker will, given a Content Type, define the matching Domain Content type.
Another, `AddDomainContentToDomainGroup`, will add the same Domain Content to its Domain Group.

#### Creating a custom worker

A custom worker will be added to customize the schema based on the content model.
The generator will iterate over a given set of objects from the repository (content type groups,
content types...), and provide workers with those.

A worker implements the `DomainSchema\SchemaWorker\SchemaWorker` interface. They implement two methods:

- `work` will use the arguments to modify the schema.
- `canWork` will test the schema and arguments, and say if the worker can run on this data.
  It must be called before calling `work`.
  **the method must also verify that the schema hasn't been worked on already**
  (usually by testing the schema itself). Yes, it is a bit redundant.

Both method receive as arguments a reference to the schema array, and an array of arguments.

Custom workers must be tagged with `ezplatform_graphql.schema_worker`.

### Data available to workers

A worker that does something for each Content Type should test in `canWork()` if the `ContentType`
argument is defined. What data is available depends on the iteration.

| **Iteration**      | ContentTypeGroup | ContentType | FieldDefinition |
| ------------------ | ---------------- | ----------- | --------------- |
| Content Type Group | Yes              | No          | No              |
| Content Type       | Yes              | Yes         | No              |
| Field Definition   | Yes              | Yes         | Yes             |

