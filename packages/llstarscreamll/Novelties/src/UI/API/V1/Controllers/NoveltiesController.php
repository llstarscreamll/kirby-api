<?php

namespace llstarscreamll\Novelties\UI\API\V1\Controllers;

use Illuminate\Http\Request;
use llstarscreamll\Novelties\UI\API\V1\Resources\NoveltyResource;
use llstarscreamll\Novelties\Contracts\NoveltyRepositoryInterface;
use llstarscreamll\Novelties\UI\API\V1\Requests\GetNoveltyRequest;

/**
 * Class NoveltiesController.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class NoveltiesController {
	/**
	 * @var \llstarscreamll\Novelties\Contracts\NoveltyRepositoryInterface
	 */
	private $noveltyRepository;

	/**
	 * @param NoveltyRepositoryInterface $noveltyRepository
	 */
	public function __construct(NoveltyRepositoryInterface $noveltyRepository) {
		$this->noveltyRepository = $noveltyRepository;
	}

	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index() {
		//
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param  \Illuminate\Http\Request    $request
	 * @return \Illuminate\Http\Response
	 */
	public function store(Request $request) {
		//
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  int                         $id
	 * @return \Illuminate\Http\Response
	 */
	public function show($id, GetNoveltyRequest $request) {
		$novelty = $this->noveltyRepository
			->with(['noveltyType', 'employee.user', 'timeClockLog'])
			->find($id);

		return NoveltyResource::make($novelty);
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  \Illuminate\Http\Request    $request
	 * @param  int                         $id
	 * @return \Illuminate\Http\Response
	 */
	public function update(Request $request, $id) {
		//
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int                         $id
	 * @return \Illuminate\Http\Response
	 */
	public function destroy($id) {
		//
	}
}
