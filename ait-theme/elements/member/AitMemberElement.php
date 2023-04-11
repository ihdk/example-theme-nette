<?php


class AitMemberElement extends AitElement
{
	public function getContentPreviewOptions()
	{
		return array(
			'layout' => 'box',
			'columns' => 1,
			'rows' => 1,
			'script' => false
		);
	}
}
