<?php 

namespace App\Http\Controllers;

use Validator;
use DB;
use App\User;
use App\PetActivityStream;
use App\ActivityStreamPhoto;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Firebase\JWT\ExpiredException;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PetActivityStreamController extends Controller
{
    public function listPetStreams()
    {
        // $listPetStreams = PetActivityStream::with('images')
        // ->get();

        $listPetStreams = DB::select('
            SELECT 
                a.username as post_by,
                b.title,
                b.description,
                b.updated_at,
                c.photo_name
            FROM
                users a,
                pet_activity_streams b,
                activity_stream_photos c
            WHERE
                a.id=b.user_id
                AND
                b.id=c.pet_activity_stream_id'
        );

        $message = "";

        if (count($listPetStreams) > 0) {
            
            $message = "List pet streams successfully retrieved";    
        }
        else {

            $message = "No pet streams created yet";
        }

        foreach ($listPetStreams as $petStream) {
            
            $petStream->photo_path = env("APP_URL")."/public/stream_image_uploaders/".$petStream->photo_name;
        }

        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $listPetStreams,
            'errors' => ''
        ], 200);
    }

    public function listMyPetStreams(Request $request)
    {
        $listPetStreams = PetActivityStream::where('user_id',$request->auth->id)
        ->with('images')
        ->get();

        $message = "";

        if (count($listPetStreams) > 0) {
            
            $message = "List pet streams successfully retrieved";    
        }
        else {

            $message = "You have no pet streams yet";
        }

        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $listPetStreams,
            'errors' => ''
        ], 200);
    }

	public function createPetStream(Request $request)
	{
		$validator = Validator::make($request->all(), [
            'title'     => 'required|min:2|max:100',
            'description'  => 'required|min:10|max:255',
            'images' => 'sometimes|required'
        ]);
        
        if ($validator->fails()) { 
            
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 412);        
        }

        try {
            
            $petActivityStream = new PetActivityStream();
            $petActivityStream->user_id = $request->auth->id;
            $petActivityStream->title = $request->title;
            $petActivityStream->description = $request->description;
            $petActivityStream->save();

            if ($request->exists('images')) {
                
                $images = $request->file('images');

                if (count($images) > 1) {

                    foreach ($images as $image) {
                        
                        $image_name = uniqid().".".strtolower($image->getClientOriginalExtension());

                        $activityStreamPhoto = new ActivityStreamPhoto();
                        $activityStreamPhoto->pet_activity_stream_id = $petActivityStream->id;
                        $activityStreamPhoto->photo_name = $image_name;
                        $activityStreamPhoto->save();

                        $image->move(pet_stream_image_path()."/",$image_name);
                    }
                }
                else {

                    $image_name = uniqid().".".strtolower($images->getClientOriginalExtension());

                    $activityStreamPhoto = new ActivityStreamPhoto();
                    $activityStreamPhoto->pet_activity_stream_id = $petActivityStream->id;
                    $activityStreamPhoto->photo_name = $image_name;
                    $activityStreamPhoto->save();

                    $images->move(pet_stream_image_path()."/",$image_name);
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'New pet activity stream successfully created',
                'errors' => ''
            ], 200);

        } catch (QueryException $e) {
            
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
                'errors' => $e
            ], 500);
        }
	}

    public function showPetStream($id=null,Request $request)
    {
        if (!is_null($id)) {
            
            try {
                
                $petActivityStream = PetActivityStream::findOrFail($id);

                return response()->json([
                    'status' => true,
                    'message' => "Pet activity stream successfully retrieved",
                    'data' => $petActivityStream,
                    'errors' => ''
                ], 200);

            } catch (ModelNotFoundException $e) {
                
                return response()->json([
                    'status' => false,
                    'message' => $e->getMessage(),
                    'errors' => $e
                ], 404);
            }
        }
        else {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => [
                    'ID cannot be null'
                ]
            ], 412); 
        }
    }

	public function updatePetStream($id=null,Request $request)
	{
		$validator = Validator::make($request->all(), [
            'title'     => 'required|min:2|max:100',
            'description'  => 'required|min:10|max:255',
            'images' => 'sometimes|required'
        ]);
        
        if ($validator->fails()) { 
            
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 412);        
        }

        if (!is_null($id)) {

            try {
                
                $petActivityStream = PetActivityStream::where('id',$id)
                ->where('user_id',$request->auth->id)
                ->with('images')
                ->firstOrFail();

                $petActivityStream->title = $request->title;
                $petActivityStream->description = $request->description;
                $petActivityStream->save();

                if ($request->exists('images')) {
                    
                    /**
                     * Remove old photos
                     */
                    if (count($petActivityStream->images) > 0) {
                        
                        foreach ($petActivityStream->images as $old_image) {
                            
                            unlink(pet_stream_image_path().DIRECTORY_SEPARATOR.$old_image->photo_name);
                        }
                    }

                    DB::delete('DELETE FROM activity_stream_photos WHERE pet_activity_stream_id = ?', [$id]);

                    /**
                     * Replace with the updated photos
                     */
                    $images = $request->file('images');

                    foreach ($images as $image) {
                        
                        $image_name = uniqid().".".strtolower($image->getClientOriginalExtension());

                        $activityStreamPhoto = new ActivityStreamPhoto();
                        $activityStreamPhoto->pet_activity_stream_id = $petActivityStream->id;
                        $activityStreamPhoto->photo_name = $image_name;
                        $activityStreamPhoto->save();

                        $image->move(pet_stream_image_path().DIRECTORY_SEPARATOR,$image_name);
                    }
                }

                return response()->json([
                    'status' => true,
                    'message' => "Pet activity stream successfully updated",
                    'errors' => ''
                ], 200);

            } catch (ModelNotFoundException $e) {
                
                return response()->json([
                    'status' => false,
                    'message' => $e->getMessage(),
                    'errors' => $e
                ], 404);
            }
        }
        else {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => [
                    'ID cannot be null'
                ]
            ], 412); 
        }
	}

	public function deletePetStream($id=null,Request $request)
	{
		if (!is_null($id)) {
            
            try {
                
                $petActivityStream = PetActivityStream::where('id',$id)
                ->where('user_id',$request->auth->id)
                ->with('images')
                ->firstOrFail();

                $petActivityStream->delete();

                DB::delete('DELETE FROM activity_stream_photos WHERE pet_activity_stream_id = ?', [$id]);

                if (count($petActivityStream->images) > 0) {
                        
                    foreach ($petActivityStream->images as $old_image) {
                        
                        unlink(pet_stream_image_path().DIRECTORY_SEPARATOR.$old_image->photo_name);
                    }
                }

                return response()->json([
                    'status' => true,
                    'message' => "Pet activity stream successfully deleted",
                    'errors' => ''
                ], 200);

            } catch (ModelNotFoundException $e) {
                
                return response()->json([
                    'status' => false,
                    'message' => $e->getMessage(),
                    'errors' => $e
                ], 404);
            }
        }
        else {

            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => [
                    'ID cannot be null'
                ]
            ], 412); 
        }
	}
}