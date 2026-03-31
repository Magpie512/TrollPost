# 📘 Database Schema Logic Overview

This document provides a clear, structured explanation of the relational design behind the SQL schema. It outlines how each table contributes to a scalable, maintainable social‑style content platform.

---

## 1. Users Table

The **users** table is the foundation of the system, storing essential account information and profile metadata for each and every user.

### Key Design Choices
- Unique **username** and **email** ensure identity integrity.
- **Password** stored as `VARCHAR(255)` supports hashed passwords (e.g., bcrypt).
- **Role** `ENUM` defines permission levels (`user`and `admins`).
- **Avatar** and **bio** fields support profile customization.
- Timestamps track account creation.

### Purpose
All other tables reference **users**, associating content, interactions, and notifications with specific accounts.

---

## 2. Posts Table

The **posts** table stores user generated content.

### Key Design Choices
- Foreign key to **users** ensures each post belongs to a valid author.
- **Slug** provides SEO or (Search Engine Optimization) friendly URLs and must be unique.
- Full‑text index on **title** and **content** enables efficient search.
- Optional **image** field supports media attachments.
- Automatic timestamps track creation and updates.

### Purpose
Forms the core content layer of the platform.

---

## 3. Comments Table

The **comments** table supports threaded, multi‑level discussions.

### Key Design Choices
- Foreign keys to **posts** and **users** ensure valid relationships.
- **Parent ID** enables nested replies.
- `ON DELETE CASCADE` for posts/users ensures cleanup when content or accounts are removed.
- `ON DELETE SET NULL` for parent comments prevents entire thread deletion.

### Purpose
Enables structured conversation threads under posts.

---

## 4. Post Likes Table

Tracks which users have liked which posts.

### Key Design Choices
- Composite primary key prevents duplicate likes.
- Foreign keys maintain relational integrity.
- Timestamp records when the like occurred.

### Purpose
Supports engagement metrics and user interaction features.

---

## 5. Tags & Post Tags

These two tables implement a many‑to‑many tagging system.

### Tags Table
- Stores unique tag names.

### Post Tags (Junction Table)
- Composite primary key ensures a post cannot have the same tag twice.
- Foreign keys enforce valid relationships.

### Purpose
Enables categorization, filtering, and topic‑based navigation.

---

## 6. Follows Table

Implements a follower/following system between users.

### Key Design Choices
- Composite primary key prevents duplicate follow relationships.
- Foreign keys ensure both follower and followed users exist.
- Timestamp records when the follow occurred.

### Purpose
Supports social features such as feeds, notifications, and user discovery.

---

## 7. Notifications Table

Stores system‑generated notifications for user activity.

### Key Design Choices
- **User ID** identifies the recipient.
- **Actor ID** identifies who triggered the notification.
- **Target type** and **target ID** allow flexible linking to posts, comments, follows, or system events.
- **Unread index** optimizes queries for unread notifications.

### Purpose
Provides a scalable, extensible notification system.

---

## Overall Architecture

This schema is designed to:

- Maintain referential integrity through foreign keys.
- Support scalable content discovery via full‑text search and tagging.
- Enable rich social interactions (likes, comments, follows).
- Provide clean data cleanup with cascading deletes.
- Allow future expansion (messaging, analytics, moderation tools).

---

## Conclusion

This schema forms a thorough foundation for a modern social content platform. Its structure balances normalization, performance, and flexibility, making it suitable for blogs, community platforms, or lightweight social networks.
