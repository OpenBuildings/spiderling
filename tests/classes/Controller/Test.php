<?php

/**
* Test Controller
*/
class Controller_Test extends Controller {

	public function action_index()
	{
		$this->response->body('Index View');
	}

	public function action_redirected()
	{
		$this->redirect('/test/final');
	}

	public function action_final()
	{
		$this->response->body('Final View');
	}

	public function action_too_many_redirects()
	{
		$this->redirect('/test/redirect1');
	}

	public function action_redirect1()
	{
		$this->redirect('/test/redirect2');
	}

	public function action_redirect2()
	{
		$this->redirect('/test/redirect3');
	}

	public function action_redirect3()
	{
		$this->redirect('/test/redirect4');
	}

	public function action_redirect5()
	{
		$this->redirect('/test/redirect6');
	}
}
