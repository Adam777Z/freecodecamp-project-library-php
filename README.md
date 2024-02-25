**freeCodeCamp** - Information Security and Quality Assurance Project
------

**Personal Library**

### User Stories:

1. I can **POST** a `title` to /api/books to add a book and returned will be the object with the `title` and a unique `id`.
2. I can **GET** /api/books to retrieve an aray of all books containing `id`, `title`, `comments`, & `commentcount`.
3. I can **GET** /api/books/{id} to retrieve a single object of a book containing `id`, `title`, an array of `comments` (empty array if no comments present), & `commentcount`.
4. I can **POST** a `comment` to /api/books/{id} to add a comment to a book and returned will be the book object similar to **GET** /api/books/{id}.
5. I can **DELETE** /api/books/{id} to delete a book from the database. Returned will be 'successfully deleted' if successful.
6. If I try to request a book that does not exist I will get a 'book not found' message.
7. I can send a **DELETE** request to /api/books to delete all books in the database. Returned will be 'complete delete successful' if successful.
8. All 10 tests required are complete and passing.