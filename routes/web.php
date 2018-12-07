<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/
use Illuminate\Http\Request;

$request = new Request();

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->post(
    'api/v1.0/auth/login','UserController@authenticate'
);

$router->post(
    'api/v1.0/auth/register','UserController@register'
);

$router->group(
    [
    	'middleware' => 'jwt.auth',
    	'prefix' => 'api/v1.0'
    ],
    function() use ($router) {

    	/*
		|--------------------------------------------------------------------------
		| User Module
		|--------------------------------------------------------------------------
		*/
    	$router->get('me','UserController@me');
    	$router->put('me','UserController@updateProfile');

    	/*
		|--------------------------------------------------------------------------
		| Pet Module Route
		|--------------------------------------------------------------------------
		*/
    	$router->get('pets','PetController@listPets');
    	$router->post('pet','PetController@createPet');
    	$router->get('pet/{id}','PetController@showPet');
    	$router->put('pet/{id}/update','PetController@updatePet');
    	$router->delete('pet/{id}/delete','PetController@deletePet');

    	/*
		|--------------------------------------------------------------------------
		| Pet Activity Stream Module Route
		|--------------------------------------------------------------------------
		*/
    	$router->get('streams','PetActivityStreamController@listPetStreams');
    	$router->get('my/streams','PetActivityStreamController@listMyPetStreams');
    	$router->post('stream','PetActivityStreamController@createPetStream');
    	$router->get('stream/{id}','PetActivityStreamController@showPetStream');
    	$router->put('stream/{id}/update','PetActivityStreamController@updatePetStream');
    	$router->delete('stream/{id}/delete','PetActivityStreamController@deletePetStream');
});