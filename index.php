<?php
$path_prefix = '';

if ( isset( $_SERVER['PATH_INFO'] ) ) {
	$path_count = substr_count( $_SERVER['PATH_INFO'], '/' ) - 1;

	for ( $i = 0; $i < $path_count; $i++ ) {
		$path_prefix .= '../';
	}

	if ( strpos( $_SERVER['PATH_INFO'], '/api/books' ) !== false ) {
		try {
			$db = new PDO( 'sqlite:database.db' );
		} catch ( PDOException $e ) {
			exit( $e->getMessage() );
		}
	}

	if ( strpos( $_SERVER['PATH_INFO'], '/api/books/' ) !== false ) {
		preg_match( '~\/api\/books\/(.*)\/?.*~', $_SERVER['PATH_INFO'], $matches );
		$book_id = (int) $matches[1];

		if ( empty( $book_id ) ) {
			header( 'Content-Type: application/json; charset=utf-8' );
			echo json_encode( [
				'error' => 'missing required field bookid',
			] );
			exit;
		}

		if ( $_SERVER['REQUEST_METHOD'] == 'GET' ) {
			$book = get_book( $book_id );

			if ( $book ) {
				header( 'Content-Type: application/json; charset=utf-8' );
				echo json_encode( $book );
				exit;
			} else {
				header( 'Content-Type: application/json; charset=utf-8' );
				echo json_encode( [
					'error' => 'book not found',
				] );
				exit;
			}
		} elseif ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
			if ( empty( $_POST['comment'] ) ) {
				header( 'Content-Type: application/json; charset=utf-8' );
				echo json_encode( [
					'error' => 'missing required field comment',
				] );
				exit;
			}

			$book = get_book( $book_id );

			if ( ! $book ) {
				header( 'Content-Type: application/json; charset=utf-8' );
				echo json_encode( [
					'error' => 'book not found',
				] );
				exit;
			}

			if ( add_book_comment( $book_id, $_POST['comment'] ) ) {
				$book = get_book( $book_id );

				if ( $book ) {
					header( 'Content-Type: application/json; charset=utf-8' );
					echo json_encode( $book );
					exit;
				} else {
					header( 'Content-Type: application/json; charset=utf-8' );
					echo json_encode( [
						'error' => 'book not found',
					] );
					exit;
				}
			} else {
				header( 'Content-Type: application/json; charset=utf-8' );
				echo json_encode( [
					'error' => 'could not add comment',
				] );
				exit;
			}
		} elseif ( $_SERVER['REQUEST_METHOD'] == 'DELETE' ) {
			$book = get_book( $book_id );

			if ( ! $book ) {
				header( 'Content-Type: application/json; charset=utf-8' );
				echo json_encode( [
					'error' => 'book not found',
				] );
				exit;
			}

			if ( delete_book( $book_id ) ) {
				header( 'Content-Type: application/json; charset=utf-8' );
				echo json_encode( [
					'result' => 'successfully deleted',
				] );
				exit;
			} else {
				header( 'Content-Type: application/json; charset=utf-8' );
				echo json_encode( [
					'error' => 'could not delete',
				] );
				exit;
			}
		} else {
			redirect_to_index();
		}
	} elseif ( strpos( $_SERVER['PATH_INFO'], '/api/books' ) !== false ) {
		if ( $_SERVER['REQUEST_METHOD'] == 'GET' ) {
			header( 'Content-Type: application/json; charset=utf-8' );
			echo json_encode( get_books() );
			exit;
		} elseif ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
			if ( empty( $_POST['title'] ) ) {
				header( 'Content-Type: application/json; charset=utf-8' );
				echo json_encode( [
					'error' => 'missing required field title',
				] );
				exit;
			}

			$title = $_POST['title'];

			if ( add_book( $title ) ) {
				$book = get_book( (int) $db->lastInsertId() );

				if ( $book ) {
					header( 'Content-Type: application/json; charset=utf-8' );
					echo json_encode( $book );
					exit;
				} else {
					header( 'Content-Type: application/json; charset=utf-8' );
					echo json_encode( [
						'error' => 'book not found',
					] );
					exit;
				}
			} else {
				header( 'Content-Type: application/json; charset=utf-8' );
				echo json_encode( [
					'error' => 'could not add the book',
				] );
				exit;
			}
		} elseif ( $_SERVER['REQUEST_METHOD'] == 'DELETE' ) {
			if ( delete_books() ) {
				header( 'Content-Type: application/json; charset=utf-8' );
				echo json_encode( [
					'result' => 'complete delete successful',
				] );
				exit;
			} else {
				header( 'Content-Type: application/json; charset=utf-8' );
				echo json_encode( [
					'error' => 'complete delete unsuccessful',
				] );
				exit;
			}
		} else {
			redirect_to_index();
		}
	} elseif ( strpos( $_SERVER['PATH_INFO'], '/api/test' ) !== false ) {
		$tests = [];

		$send_data = [
			'title' => 'Title',
		];
		$data = post_api_data( '/api/books', $send_data );
		$tests[] = [
			'title' => 'POST /api/books with title => create book, expect book object: Test POST /api/books with title',
			'data' => $send_data,
			'passed' => (
				! empty( $data['id'] )
				&&
				! empty( $data['title'] )
				&&
				$data['title'] == $send_data['title']
				&&
				isset( $data['comments'] )
				&&
				empty( $data['comments'] )
				&&
				isset( $data['commentcount'] )
				&&
				$data['commentcount'] === 0
			),
		];
		$id = $data['id'];

		$send_data = [
			'title' => '',
		];
		$data = post_api_data( '/api/books', $send_data );
		$tests[] = [
			'title' => 'POST /api/books with title => create book, expect book object: Test POST /api/books with no title given',
			'data' => $send_data,
			'passed' => isset( $data['error'] ) && $data['error'] == 'missing required field title',
		];

		$send_data = [];
		$data = get_api_data( '/api/books' );
		$tests[] = [
			'title' => 'GET /api/books => array of books: Test GET /api/books',
			'data' => $send_data,
			'passed' => (
				isset( $data[0]['id'] )
				&&
				isset( $data[0]['title'] )
				&&
				isset( $data[0]['comments'] )
				&&
				isset( $data[0]['commentcount'] )
			),
		];

		$send_data = [];
		$data = get_api_data( "/api/books/$id" );
		$tests[] = [
			'title' => 'GET /api/books/[id] => book object with [id]: Test GET /api/books/[id] with valid id in db',
			'data' => $send_data,
			'passed' => (
				isset( $data['id'] )
				&&
				isset( $data['title'] )
				&&
				isset( $data['comments'] )
				&&
				isset( $data['commentcount'] )
			),
		];

		$send_data = [];
		$data = get_api_data( '/api/books/-1' );
		$tests[] = [
			'title' => 'GET /api/books/[id] => book object with [id]: Test GET /api/books/[id] with id not in db',
			'data' => $send_data,
			'passed' => isset( $data['error'] ) && $data['error'] == 'book not found',
		];

		$send_data = [
			'comment' => 'Comment',
		];
		$data = post_api_data( "/api/books/$id", $send_data );
		$tests[] = [
			'title' => 'POST /api/books/[id] => add comment, expect book object with id: Test POST /api/books/[id] with comment',
			'data' => $send_data,
			'passed' => (
				isset( $data['id'] )
				&&
				isset( $data['title'] )
				&&
				isset( $data['comments'] )
				&&
				! empty( $data['comments'] )
				&&
				$data['comments'][ count( $data['comments'] ) - 1 ] == $send_data['comment']
				&&
				isset( $data['commentcount'] )
			),
		];

		$send_data = [];
		$data = post_api_data( "/api/books/$id", $send_data );
		$tests[] = [
			'title' => 'POST /api/books/[id] => add comment, expect book object with id: Test POST /api/books/[id] without comment field',
			'data' => $send_data,
			'passed' => isset( $data['error'] ) && $data['error'] == 'missing required field comment',
		];

		$send_data = [
			'comment' => 'Comment',
		];
		$data = post_api_data( '/api/books/-1', $send_data );
		$tests[] = [
			'title' => 'POST /api/books/[id] => add comment, expect book object with id: Test POST /api/books/[id] with comment, id not in db',
			'data' => $send_data,
			'passed' => isset( $data['error'] ) && $data['error'] == 'book not found',
		];

		$send_data = [];
		$data = post_api_data( "/api/books/$id", $send_data, 'DELETE' );
		$tests[] = [
			'title' => "DELETE /api/books/[id] => delete book object id: Test DELETE /api/books/[id] with valid id in db",
			'data' => $send_data,
			'passed' => isset( $data['result'] ) && $data['result'] == 'successfully deleted',
		];

		$send_data = [];
		$data = post_api_data( '/api/books/-1', $send_data, 'DELETE' );
		$tests[] = [
			'title' => "DELETE /api/books/[id] => delete book object id: Test DELETE /api/books/[id] with id not in db",
			'data' => $send_data,
			'passed' => isset( $data['error'] ) && $data['error'] == 'book not found',
		];

		header( 'Content-Type: application/json; charset=utf-8' );
		echo json_encode( $tests );
		exit;
	} else {
		redirect_to_index();
	}
}

function redirect_to_index() {
	global $path_prefix;

	if ( $path_prefix == '' ) {
		$path_prefix = './';
	}

	header( "Location: $path_prefix" );
	exit;
}

function get_api_data( $path ) {
	$url = 'http' . ( ! empty( $_SERVER['HTTPS'] ) ? 's' : '' ) . '://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];

	if ( isset( $_SERVER['PATH_INFO'] ) ) {
		$url = str_replace( $_SERVER['PATH_INFO'], '', $url ) . '/';
	}

	$url .= ltrim( $path, '/' );

	$ch = curl_init( $url );

	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

	$result = curl_exec( $ch );

	$return = $result ? json_decode( $result, true ) : [];

	curl_close( $ch );

	return $return;
}

function post_api_data( $path, $data, $method = 'POST' ) {
	$url = 'http' . ( ! empty( $_SERVER['HTTPS'] ) ? 's' : '' ) . '://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];

	if ( isset( $_SERVER['PATH_INFO'] ) ) {
		$url = str_replace( $_SERVER['PATH_INFO'], '', $url ) . '/';
	}

	$url .= ltrim( $path, '/' );

	if ( $method != 'POST' ) {
		$data = http_build_query( $data );
	}

	$ch = curl_init( $url );

	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	// curl_setopt( $ch, CURLOPT_POST, true );
	curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, $method );
	curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );

	$result = curl_exec( $ch );

	$return = $result ? json_decode( $result, true ) : [];

	curl_close( $ch );

	return $return;
}

function get_books() {
	global $db;

	$query = $db->query( "SELECT * FROM books" );
	$result = $query->fetchAll( PDO::FETCH_ASSOC );

	if ( $result ) {
		foreach ( $result as $key => $value ) {
			$result[$key]['id'] = (int) $result[$key]['id'];
			$result[$key]['comments'] = json_decode( $result[$key]['comments'], true );
			$result[$key]['commentcount'] = count( $result[$key]['comments'] );
		}
	}

	return $result ? $result : [];
}

function get_book( $book_id ) {
	global $db;

	$query = $db->query( "SELECT * FROM books WHERE id = {$db->quote( $book_id )}" );
	$result = $query->fetchAll( PDO::FETCH_ASSOC );

	if ( $result ) {
		$result[0]['id'] = (int) $result[0]['id'];
		$result[0]['comments'] = json_decode( $result[0]['comments'], true );
		$result[0]['commentcount'] = count( $result[0]['comments'] );
	}

	return $result ? $result[0] : false;
}

function add_book( $title ) {
	global $db;

	$data = [
		'title' => $title,
	];
	$sth = $db->prepare( 'INSERT INTO books (title) VALUES (:title)' );
	return $sth->execute( $data );
}

function add_book_comment( $book_id, $comment ) {
	global $db;

	$book = get_book( $book_id );

	if ( $book ) {
		$book['comments'][] = $comment;

		$data = [
			'id' => (int) $book_id,
			'comments' => json_encode( $book['comments'] ),
		];
		$sth = $db->prepare( 'UPDATE books SET comments = :comments WHERE id = :id' );
		return $sth->execute( $data );
	} else {
		return false;
	}
}

function delete_book( $id ) {
	global $db;

	$data = [
		'id' => (int) $id,
	];
	$sth = $db->prepare( 'DELETE FROM books WHERE id = :id' );
	return $sth->execute( $data );
}

function delete_books() {
	global $db;

	$sth = $db->prepare( 'DELETE FROM books' );
	return $sth->execute();
}
?><!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Personal Library</title>
	<meta name="description" content="freeCodeCamp - Information Security and Quality Assurance Project: Personal Library">
	<link rel="icon" type="image/x-icon" href="<?php echo $path_prefix; ?>favicon.ico">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<link rel="stylesheet" href="<?php echo $path_prefix; ?>assets/css/bootstrap.min.css">
	<link rel="stylesheet" href="<?php echo $path_prefix; ?>assets/css/style.min.css">
	<script src="<?php echo $path_prefix; ?>assets/js/script.min.js"></script>
</head>
<body>
	<div class="container">
		<div class="p-4 my-4 bg-light rounded-3">
			<div class="row">
				<div class="col">
					<header>
						<h1 id="title" class="text-center">Personal Library</h1>
					</header>

					<div id="userstories">
						<h2>User Stories:</h2>
						<ol>
							<li>I can <b>POST</b> a <code>title</code> to /api/books to add a book and returned will be the object with the <code>title</code> and a unique <code>id</code>.</li>
							<li>I can <b>GET</b> /api/books to retrieve an aray of all books containing <code>id</code>, <code>title</code>, <code>comments</code>, & <code>commentcount</code>.</li>
							<li>I can <b>GET</b> /api/books/{id} to retrieve a single object of a book containing <code>id</code>, <code>title</code>, an array of <code>comments</code> (empty array if no comments present), & <code>commentcount</code>.</li>
							<li>I can <b>POST</b> a <code>comment</code> to /api/books/{id} to add a comment to a book and returned will be the book object similar to <b>GET</b> /api/books/{id}.</li>
							<li>I can <b>DELETE</b> /api/books/{id} to delete a book from the database. Returned will be 'successfully deleted' if successful.</li>
							<li>If I try to request a book that does not exist I will get a 'book not found' message.</li>
							<li>I can send a <b>DELETE</b> request to /api/books to delete all books in the database. Returned will be 'complete delete successful' if successful.</li>
							<li>All 10 <a href="<?php echo $path_prefix; ?>api/test" target="_blank">tests</a> required are complete and passing.</li>
						</ol>
						<div class="table-responsive-sm">
							<table class="table">
								<thead>
									<tr>
										<th scope="col">API</th>
										<th scope="col">GET</th>
										<th scope="col">POST</th>
										<th scope="col">DELETE</th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<th scope="row"><a href="<?php echo $path_prefix; ?>api/books" target="_blank">/api/books</a></th>
										<td>list all books</td>
										<td>add new book</td>
										<td>delete all books</td>
									</tr>
									<tr>
										<th scope="row"><a href="<?php echo $path_prefix; ?>api/books/1" target="_blank">/api/books/1</a></th>
										<td>show book 1</td>
										<td>add comment to 1</td>
										<td>delete 1</td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>

					<hr>

					<div id="sampleposting" class="text-center">
						<h2>Test API responses:</h2>
						<div class="row">
							<div class="col">
								<h4>Test post to /api/books</h4>
								<form action="<?php echo $path_prefix; ?>api/books" method="post">
									<label for="bookTitle">Book Title:</label>
									<input type="text" name="title" id="bookTitle" class="form-control mb-2" placeholder="Book Title">
									<input type="submit" class="btn btn-primary" value="Submit">
								</form>
							</div>
							<div class="col">
								<h4>Test post to /api/books/{bookid}</h4>
								<form id="comment-test" action="" method="post">
									<label for="idinputtest">Book ID to comment on:</label>
									<input type="text" name="id" id="idinputtest" class="form-control mb-2" placeholder="Book ID to comment on">
									<label for="bookComment">Comment:</label>
									<input type="text" name="comment" id="bookComment" class="form-control mb-2" placeholder="Comment">
									<input type="submit" class="btn btn-primary" value="Submit">
								</form>
							</div>
						</div>
					</div>

					<hr>

					<div id="sampleui">
						<h2>Sample Front-End:</h2>
						<div class="row">
							<div class="col text-center">
								<form id="new-book-form">
									<input type="text" name="title" class="form-control mb-2" placeholder="New Book Title">
									<button type="submit" id="new-book" class="btn btn-primary" value="Submit">Submit New Book</button>
								</form>
								<div class="mt-2 text-start">
									<button type="button" id="delete-all-books" class="btn btn-danger">Delete all books...</button>
								</div>
							</div>
							<div class="col">
								<h4>Books:</h4>
								<ul id="books"></ul>
								<div id="book-details">
									<p id="book-title">Select a book to see its details and comments</p>
									<ol id="book-comments"></ol>
									<div id="book-form"></div>
								</div>
							</div>
						</div>
					</div>

					<hr>

					<div class="footer text-center">by <a href="https://www.freecodecamp.org" target="_blank">freeCodeCamp</a> (ISQA1) & <a href="https://www.freecodecamp.org/adam777" target="_blank">Adam</a> | <a href="https://github.com/Adam777Z/freecodecamp-project-library-php" target="_blank">GitHub</a></div>
				</div>
			</div>
		</div>
	</div>
</body>
</html>