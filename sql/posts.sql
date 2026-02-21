/* Only realy needs to have message posting, deleting and editing. */
/* No author needed as the site doesnt facilitate user accounts. */

CREATE TABLE posts (
    id SERIAL PRIMARY KEY, /* Auto-incrementing ID for each post */
    content TEXT NOT NULL, /* The content of the post */
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, /* Timestamp for when the post was created */
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP /* Timestamp for when the post was last updated */
);

/* Index on created_at for faster retrieval of posts in chronological order. */
/* ALTER TABLE posts ADD INDEX idx_posts_created_at (created_at); */