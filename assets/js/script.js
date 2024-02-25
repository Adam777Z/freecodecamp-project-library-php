document.addEventListener( 'DOMContentLoaded', ( event ) => {
	var path_prefix = window.location.pathname;
	var books = [];
	var books_html = '';
	var comments_html = '';

	fetch( path_prefix + 'api/books', {
		'method': 'GET',
	} )
	.then( ( response ) => {
		if ( response['ok'] ) {
			return response.json();
		} else {
			throw 'Error';
		}
	} )
	.then( ( data ) => {
		books = data;
		load_books();
	} )
	.catch( ( error ) => {
		console.log( error );
	} );

	function load_books() {
		if ( books.length ) {
			books_html = '';

			for ( let [i, val] of Object.entries( books ) ) {
				books_html += `<li class="book-item" id="book-${i}">${val['title']} - ${val['commentcount']} comment${val['commentcount'] > 1 ? 's' : ''}</li>`;
			}

			document.querySelector('#books').innerHTML = books_html;
		}
	}

	// For #sampleposting to update form action URL to test input Book ID
	document.querySelector( '#comment-test' ).addEventListener( 'submit', ( event2 ) => {
		let id = document.querySelector( '#idinputtest' ).value;
		event2.target.setAttribute( 'action', `${path_prefix}api/books/${id}` );
	} );

	document.querySelector( '#new-book' ).addEventListener( 'click', ( event2 ) => {
		event2.preventDefault();

		fetch( path_prefix + 'api/books', {
			'method': 'POST',
			'body': new URLSearchParams( new FormData( document.querySelector( '#new-book-form' ) ) ),
		} )
		.then( ( response ) => {
			if ( response['ok'] ) {
				return response.text();
			} else {
				throw 'Error';
			}
		} )
		.then( ( data ) => {
			try {
				data = JSON.parse( data );
			} catch ( error ) {
				// console.log( error );
			}

			if ( data['error'] !== undefined ) {
				alert( data['error'] );
			} else {
				document.querySelector( '#new-book-form' ).reset();

				// update list
				data['commentcount'] = 0;
				books.push( data );
				load_books();
			}
		} )
		.catch( ( error ) => {
			console.log( error );
		} );
	} );

	document.querySelector( '#delete-all-books' ).addEventListener( 'click', ( event2 ) => {
		if ( confirm( 'Are you sure you want to delete all books?' ) ) {
			fetch( path_prefix + 'api/books', {
				'method': 'DELETE',
			} )
			.then(( response ) => {
				if ( response['ok'] ) {
					return response.text();
				} else {
					throw 'Error';
				}
			} )
			.then(( data ) => {
				try {
					data = JSON.parse( data );
				} catch ( error ) {
					// console.log( error );
				}

				if ( data['error'] !== undefined ) {
					alert( data['error'] );
				} else {
					// update list
					books = [];
					books_html = '';

					document.querySelector('#books').innerHTML = '';
					document.querySelector('#book-title').innerHTML = 'Select a book to see its details and comments';
					document.querySelector('#book-comments').innerHTML = '';
					document.querySelector('#book-form').innerHTML = '';

					alert( data['result'] );
				}
			} )
			.catch( ( error ) => {
				console.log( error );
			} );
		}
	} );

	document.querySelector( '#books' ).addEventListener( 'click', ( event2 ) => {
		if ( event2.target.closest( '.book-item' ) ) {
			let this_id = event2.target.closest( '.book-item' ).id.replace( 'book-', '' );
			document.querySelector( '#book-title' ).innerHTML = '<b>' + books[this_id]['title'] + '</b> (id: ' + books[this_id]['id'] + ')';

			fetch( path_prefix + 'api/books/' + books[this_id]['id'], {
				'method': 'GET',
			} )
			.then( ( response ) => {
				if ( response['ok'] ) {
					return response.text();
				} else {
					throw 'Error';
				}
			} )
			.then( ( data ) => {
				try {
					data = JSON.parse( data );
				} catch ( error ) {
					// console.log( error );
				}

				comments_html = '';

				for ( let [i, val] of Object.entries( data['comments'] ) ) {
					comments_html += `<li>${val}</li>`;
				}

				let new_comment_form_html = `
				<form id="newCommentForm">
					<input type="text" class="form-control mb-2" id="commentToAdd" name="comment" placeholder="New Comment">
					<button type="button" class="btn btn-primary mb-2 addComment" id="${data['id']}" data-i="${this_id}">Add Comment</button><br>
					<button type="button" class="btn btn-danger deleteBook" id="${data['id']}" data-i="${this_id}">Delete Book</button>
				</form>
				`;

				document.querySelector( '#book-comments' ).innerHTML = comments_html;
				document.querySelector( '#book-form' ).innerHTML = new_comment_form_html;
			} )
			.catch( ( error ) => {
				console.log( error );
			} );
		}
	} );

	document.querySelector( '#book-details' ).addEventListener( 'click', ( event2 ) => {
		if ( event2.target.closest( '.addComment' ) ) {
			event2.preventDefault();

			let this_id = event2.target.closest( '.addComment' ).dataset['i'];
			let newComment = document.querySelector( '#commentToAdd' ).value;

			fetch( path_prefix + 'api/books/' + event2.target.closest( '.addComment' ).id, {
				'method': 'POST',
				'body': new URLSearchParams( new FormData( document.querySelector( '#newCommentForm' ) ) ),
			} )
			.then( ( response ) => {
				if ( response['ok'] ) {
					return response.text();
				} else {
					throw 'Error';
				}
			} )
			.then( ( data ) => {
				try {
					data = JSON.parse( data );
				} catch ( error ) {
					// console.log( error );
				}

				if ( data['error'] !== undefined ) {
					alert( data['error'] );

					if ( data['error'] == 'book not found' ) {
						// update list
						document.querySelector( '#book-title' ).innerHTML = 'Select a book to see its details and comments';
						document.querySelector( '#book-comments' ).innerHTML = '';
						document.querySelector( '#book-form' ).innerHTML = '';
						document.querySelector( '#book-' + this_id ).remove();
						books.splice( this_id, 1 );
					}
				} else {
					document.querySelector( '#commentToAdd' ).value = '';
					comments_html += `<li>${newComment}</li>`; // add new comment to bottom of list
					document.querySelector( '#book-comments' ).innerHTML = comments_html;
					books[this_id]['commentcount']++;
					document.querySelector( '#book-' + this_id ).innerHTML = books[this_id]['title'] + ' - ' + books[this_id]['commentcount'] + ' comment' + ( books[this_id]['commentcount'] > 1 ? 's' : '' );
				}
			} )
			.catch( ( error ) => {
				console.log( error );
			} );
		}

		if ( event2.target.closest( '.deleteBook' ) ) {
			event2.preventDefault();

			if ( confirm( 'Are you sure you want to delete this book?' ) ) {
				let this_id = event2.target.closest( '.deleteBook' ).dataset['i'];

				fetch( path_prefix + 'api/books/' + event2.target.closest( '.deleteBook' ).id, {
					'method': 'DELETE',
				} )
				.then( ( response ) => {
					if ( response['ok'] ) {
						return response.text();
					} else {
						throw 'Error';
					}
				} )
				.then( ( data ) => {
					try {
						data = JSON.parse( data );
					} catch ( error ) {
						// console.log( error );
					}

					if ( data['error'] !== undefined ) {
						alert( data['error'] );
					} else {
						alert( data['result'] );

						// update list
						document.querySelector( '#book-title' ).innerHTML = 'Select a book to see its details and comments';
						document.querySelector( '#book-comments' ).innerHTML = '';
						document.querySelector( '#book-form' ).innerHTML = '';
						document.querySelector( '#book-' + this_id ).remove();
						books.splice(this_id, 1);
					}
				} )
				.catch( ( error ) => {
					console.log( error );
				} );
			}
		}
	} );
} );