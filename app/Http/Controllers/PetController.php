<?php 

namespace App\Http\Controllers;

use Validator;
use App\User;
use App\Pet;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Firebase\JWT\ExpiredException;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PetController extends Controller
{
	public function listPets(Request $request)
	{
		$pets = Pet::where('user_id',$request->auth->id)
		->get();

		$message = "";

		if (count($pets) > 0) {

			$message = "Pet records successfully retrieved";
		}
		else {

			$message = "You have no pet record at the moment";
		}

		foreach ($pets as $pet) {
			
			$pet->type = $this->getPetType($pet->type);
			$pet->colour = $this->getPetColour($pet->colour);
		}

		return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $pets,
            'errors' => ''
        ], 200);
	}

	public function createPet(Request $request)
	{
		$validator = Validator::make($request->all(), [
            'type'     => 'required|integer',
            'colour'  => 'required|integer',
            'quantity'  => 'required|integer',
        ]);
        
        if ($validator->fails()) { 
            
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 412);        
        }

        try {
        	
        	$checkDuplicate = Pet::where('type',$request->type)
        	->where('user_id',$request->auth->id)
        	->count();

        	if ($checkDuplicate > 0) {
        		
        		return response()->json([
		            'status' => false,
		            'message' => 'Duplicate record.',
		            'errors' => ''
		        ], 409);
        	}
        	else {

		        $pet = new Pet();
		        $pet->user_id = $request->auth->id;
		        $pet->type = $request->type;
		        $pet->colour = $request->colour;
		        $pet->qty = $request->quantity;
		        $pet->save();

		        return response()->json([
		            'status' => true,
		            'message' => 'New pet record successfully created',
		            'errors' => ''
		        ], 200);
        	}

        } catch (Exception $e) {
        	
        	return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
                'errors' => $e
            ], 500);
        }
	}

	public function showPet($id=null,Request $request)
	{
		try {
			
			$pet = Pet::where('user_id',$request->auth->id)
			->where('id',$id)
			->firstOrFail();

			return response()->json([
	            'status' => true,
	            'message' => "Pet details successfully retrieved",
	            'data' => $pet,
	            'errors' => ''
	        ], 200);

		} catch (ModelNotFoundException $e) {
			
			return response()->json([
	            'status' => true,
	            'message' => $e->getMessage(),
	            'errors' => $e
	        ], 404);
		}
	}

	public function updatePet($id=null,Request $request)
	{
		if (!is_null($id)) {

			$validator = Validator::make($request->all(), [
	            'type'     => 'required|integer',
	            'colour'  => 'required|integer',
	            'quantity'  => 'required|integer',
	        ]);
	        
	        if ($validator->fails()) { 
	            
	            return response()->json([
	                'status' => false,
	                'message' => 'Validation error',
	                'errors' => $validator->errors()
	            ], 412);        
	        }

	        try {
        	
		        $pet = Pet::where('id',$id)
		        ->where('user_id',$request->auth->id)
		        ->firstOrFail();

		        $pet->type = $request->type;
		        $pet->colour = $request->colour;
		        $pet->qty = $request->quantity;
		        $pet->save();

		        return response()->json([
		            'status' => true,
		            'message' => 'Pet record successfully updated',
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
	            	'ID is null'
	            ]
	        ], 412);
	    }
	}

	public function deletePet($id=null,Request $request)
	{
		if (!is_null($id)) {

			try {
				
				$pet = Pet::where('id',$id)
		        ->where('user_id',$request->auth->id)
		        ->firstOrFail();

		        $pet->delete();

		        return response()->json([
		            'status' => true,
		            'message' => 'Pet record successfully deleted',
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

		}
	}

	public function getPetType($type=1)
	{
		switch ($type) {
			case 1:
				return "Bats";
				break;
			
			case 2:
				return "Cats";
				break;
			
			case 3:
				return "Dog";
				break;
			
			case 4:
				return "Elephants";
				break;
			
			case 5:
				return "Giraffes";
				break;
			
			case 6:
				return "Horses";
				break;
		}
	}

	public function getPetColour($colour=1)
	{
		switch ($colour) {
			case 1:
				return "Black";
				break;
			
			case 2:
				return "Yellow";
				break;
			
			case 3:
				return "Green";
				break;
			
			case 4:
				return "White";
				break;
		}
	}
}