# movabletype-importer-fixers
A collection of tools and documentation related to migrating sites from Movable Type to WordPress.

### Why? 🧐

- Movable Type to WordPress importer (Default one from wpcomdotorg) have some issues. To fix those issues after import, this tool will help fixing the issues.

- As Movable Type is being updated day by day, There are some outdated support needs to be handled manually.

### Environment Setup: 💻

- To start with this tool, A SQL dump of database is required.
- In the same wordpress env or any external env this database can be imported. (Make sure that the database should be accessible through WordPress env where this migration needs to be done)
- There are 4 different Globals needed for the connection of second database in WordPress installation. (in wp-config.php)

  1. `MT_DB_HOST` : This can be the endpoint or IP of instance where Movable Type database is hosted. If database imported in same WordPress database place then no need of this Global var, It will pick by default same env.

  2. `MT_DB_USER` : This can be the username of Movable Type database, to access the database. If database imported in same WordPress database place then no need of this Global var, It will pick by default same env.

  3. `MT_DB_PASSWORD` : This can be the password of Movable Type database, to access the database. If database imported in same WordPress database place then no need of this Global var, It will pick by default same env.
  
  4. `MT_DB_NAME` : This is the Movable Type database name, to access the database. This variable is mandatory.

- Test the database connection by this command: `wp mt-wp-cli test-db-connection `

- If connection is failing then it will prompt a message to re-check the connection setup.

### How it works? 🤔

- To migrate the Movable Type tags to WP tags: `wp mt-wp-cli migrate-tags [options]`
  - *Why?* : Movable Type to WordPress importer is not importing tags with the content/posts.
  - Options:
    - `--dry-run` : true/false [To indicate the dry-rn mode or not]
    - `--blog-id` : (int) Movable Type blog ID <default: 1> [This is needed only when Movable type has multiple blogs under one setup/database]
  - Example: 
    ````
    > wp mt-wp-cli migrate-tags --dry-run=true --blog-id=1
    Success: Total 100 posts will be processed.
    
    Success: Total time taken by this script: 1 second
    
    > wp mt-wp-cli migrate-tags --dry-run=false --blog-id=1
    Success: Total 100 posts have been processed.
    
    Success: Total time taken by this script: 1 second
    ````

- To overwrite the WordPress post content from MT post content: `wp mt-wp-cli overwrite-content`
  - *Why?* : 
    - Movable Type to WordPress importer is manipulating the markdown if that is implemented on Movable type platform. Because of this markdown is being broken in WordPress.
    - Movable Type has a extra extended content fields. That is also not being migrated in import.
  - Options:
    - `--dry-run` : true/false [To indicate the dry-rn mode or not]
  - Example: 
    ````
    > wp mt-wp-cli overwrite-content --dry-run=true
    Success: Total 100 posts will be processed.
    
    Success: Total time taken by this script: 1 second
    
    > wp mt-wp-cli overwrite-content --dry-run=false
    Success: Total 100 posts have been processed.
    
    Success: Total time taken by this script: 1 second
    ````

- To migrate the custom meta fields from Movable Type: `wp mt-wp-cli migrate-meta-values`
  - *Why?* : Movable Type to WordPress importer is not importing custom meta fields and values. [Specifically when 3rd party Movable Type plugin is used]
  - Options:
    - `--dry-run` : true/false [To indicate the dry-rn mode or not]
    - `--blog-id` : (int) Movable Type blog ID <default: 1> [This is needed only when Movable type has multiple blogs under one setup/database]
    - `--filed-type` : postmeta/ACF <default: postmeta> [to define where these meta fields needs to be migrated. either postmeta or ACF custom fields]
  - Example: 
    ````
    > wp mt-wp-cli migrate-meta-values --field-type=ACF --dry-run=true
    Success: Total 100 posts will be processed.
    
    Success: Total time taken by this script: 1 second
    
    > wp mt-wp-cli migrate-meta-values --field-type=ACF --dry-run=false
    Success: Total 100 posts have been processed.
    
    Success: Total time taken by this script: 1 second
    ````

- To match Movable Type mark down with Jetpack markdown: `wp mt-wp-cli migrate-meta-values`
  - *Why?* : Only when Movable Type used a markdown in post content. This will just save the posts without any change and Jetpack will parse it's markdown. [This needs Jetpack activated and markdown parsing enabled from its settings. ref - https://jetpack.com/support/markdown/]
  - Options:
    - `--dry-run` : true/false [To indicate the dry-rn mode or not]
    - `--limit` : (int) Limit or number of posts being processed in one interval <default: 100>
    - `--page` : (int) Page number if limit is used and want to process for next pages <default: 1>
    - `--post-type` : Post Type <default: post>
  - Example: 
    ````
    > wp mt-wp-cli sync-jetpack-markdown --limit=1000 --page=1 --dry-run=true
    Success: Total 100 posts will be processed.
    
    Success: Total time taken by this script: 1 second
    
    > wp mt-wp-cli sync-jetpack-markdown --limit=1000 --page=1 --dry-run=false
    Success: Total 100 posts have been processed.
    
    Success: Total time taken by this script: 1 second
    ````

### Contribute

#### Reporting a bug 🐞

Before creating a new issue, do browse through the [existing issues](https://github.com/a8cteam51/movabletype-importer-fixers/issues) for resolution or upcoming fixes. 

If you still need to [log an issue](https://github.com/a8cteam51/movabletype-importer-fixers/issues/new), making sure to include as much detail as you can, including clear steps to reproduce your issue if possible.
