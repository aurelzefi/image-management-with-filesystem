<?php

$app->group(['prefix' => 'images'], function () use ($app) {
	// GET routes...
	$app->get('', 'ImagesController@index');
	$app->get('{id}', 'ImagesController@show');
	$app->get('{id}/representation', 'ImagesController@showRepresentation');
	$app->get('{id}/download', 'ImagesController@download');

	// POST routes...
	$app->post('', 'ImagesController@store');

	// PUT routes...
	$app->put('{id}', 'ImagesController@update');
	$app->put('{id}/resize', 'ImagesController@resize');
	$app->put('{id}/insert', 'ImagesController@insert');
	$app->put('{id}/crop', 'ImagesController@crop');
	$app->put('{id}/turn-greyscale', 'ImagesController@turnGreyscale');
	$app->put('{id}/set-opacity', 'ImagesController@setOpacity');
	$app->put('{id}/change-brightness', 'ImagesController@changeBrightness');
	$app->put('{id}/rotate', 'ImagesController@rotate');
	$app->put('{id}/encode', 'ImagesController@encode');

	// DELETE routes...
	$app->delete('{id}', 'ImagesController@destroy');
});
