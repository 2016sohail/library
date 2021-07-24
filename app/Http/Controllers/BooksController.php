<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use App\User;
use App\Book;
use App\BookUser;
use JWTAuth;
use DB;

class BooksController extends Controller
{
    public function store(Request $request)
    {
        try
        {
            $data = $request->only('book_name', 'author', 'cover_image', 'b_id');
            $validator = Validator::make($data, [
                'book_name' => 'required|max:255',
                'author' => 'required|regex:/^[a-zA-Z]+$/u|max:255',
                'cover_image' => 'required'
            ]);

            //Send failed response if request is not valid
            if ($validator->fails()) {
                return response()->json(['error' => $validator->messages()], 200);
            }

            DB::beginTransaction();
            
            if(array_key_exists('b_id', $data))
            {
                $book = Book::find($data['b_id']);
                $msg = 'Book edited successfully';
                if(empty($book))
                {
                    $book = new Book();
                    $msg = 'Book added successfully';
                }
            }
            else
            {
                $book = new Book();
                $msg = 'Book added successfully';
            }
            $book->fill($data);
            $book->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $msg,
                'data' => $book
            ], Response::HTTP_OK);
        }
        catch(\Throwable $th)
        {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => $th->getMessage()." on Line: ".$th->getLine()." File: ".$th->getFile(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function delete($b_id)
    {
        try
        {
            DB::beginTransaction();
            $book = Book::find($b_id);
            if(empty($book))
            {
                $msg = "Book not found.";
            }
            else
            {
                $book->delete();
                $msg = "Book Deleted.";
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => $msg
            ], Response::HTTP_OK);
        }
        catch(\Throwable $th)
        {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => $th->getMessage()." on Line: ".$th->getLine()." File: ".$th->getFile(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($b_id = '')
    {
        try
        {
            $msg = "";
            if(empty($b_id))
            {
                $book = Book::get();
            }
            else
            {
                $book = Book::find($b_id);
                if(empty($book))
                {
                    $msg = "Book not found.";
                }
            }
            return response()->json([
                'success' => true,
                'message' => $msg,
                'book' => $book
            ], Response::HTTP_OK);
        }
        catch(\Throwable $th)
        {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage()." on Line: ".$th->getLine()." File: ".$th->getFile(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function add_user(Request $request)
    {
        try
        {
            $msg = "";
            $b_id = $request->b_id;
            $u_id = $request->u_id;
            if(empty($b_id) || empty($u_id))
            {
                $msg = "Please provide valid book and user ids.";
            }
            else
            {
                $book = Book::find($b_id);
                if(empty($book))
                {
                    throw new \Exception( "Book not found.");
                }
                $user = User::find($u_id);
                if(empty($user))
                {
                    throw new \Exception( "User not found.");
                }
                $assigned = BookUser::where('b_id', $b_id)->where('u_id', $u_id)->where('is_active', BookUser::ACTIVE)->whereNull('end_date')->get();
                if(count($assigned))
                {
                    $msg = "Already assigned.";
                    if($request->has('remove') && $request->remove)
                    {
                        BookUser::where('b_id', $b_id)->where('u_id', $u_id)->where('is_active', BookUser::ACTIVE)->update([
                            'end_date' => date('Y-m-d H:i:s'),
                            'is_active' => BookUser::INACTIVE
                        ]);
                        $msg = "Removed User.";
                    }
                }
                else
                {
                    BookUser::create([
                        'b_id' => $b_id,
                        'u_id' => $u_id,
                        'start_date' => date('Y-m-d H:i:s'),
                        'is_active' => BookUser::ACTIVE
                    ]);
                    $msg = "Book assigned.";
                }

            }
            return response()->json([
                'success' => true,
                'message' => $msg
            ], Response::HTTP_OK);
        }
        catch(\Throwable $th)
        {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function user_books(Request $request)
    {
        try
        {
            $msg = "";
            if(empty($request->u_id))
            {
                throw new \Exception('Please provide a user.');
            }
            else
            {
                $books = BookUser::with('userBelongs', 'bookBelongs')->where('u_id', $request->u_id)->where('is_active', BookUser::ACTIVE)->get();
                if(count($books) == 0)
                {
                    $msg = "No books found for this user.";
                }
                else
                {
                    $msg = "Books found.";
                }
            }
            return response()->json([
                'success' => true,
                'message' => $msg,
                'books' => $books
            ], Response::HTTP_OK);
        }
        catch(\Throwable $th)
        {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage()." on Line: ".$th->getLine()." File: ".$th->getFile(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
