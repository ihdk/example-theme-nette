<?php


class AitVariableOptionControl extends AitOptionControl
{

	protected function init()
	{
		$this->isLessVar = true;
	}



	public function getHtml()
	{
		return ''; // it has not visual control, it's just variable
	}

}
