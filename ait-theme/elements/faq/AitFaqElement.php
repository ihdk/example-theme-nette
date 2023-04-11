<?php


class AitFaqElement extends AitElement
{
	public function getContentPreviewOptions()
	{
		return array(
			'layout' => 'list',
			'columns' => 1,
			'rows' => $this->option('count'),
		);
	}
}
