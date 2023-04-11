<?php


class AitPriceTableElement extends AitElement
{
	public function getContentPreviewOptions()
	{
		$layout = $this->option('layout');
		if (!isset($layout) || $layout == 'horizontal') {
			$columns = 3;
			$rows = 1;
		} else {
			$columns = 1;
			$rows = 3;
		}

		return array(
			'columns' => $columns,
			'rows' => $rows
		);
	}
}
