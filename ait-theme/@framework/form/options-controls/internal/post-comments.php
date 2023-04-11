<?php


class AitPostCommentsOptionControl extends AitOptionControl
{


	protected function control()
	{
		$specialPages = aitOptions()->getSpecialCustomPages();
		$oid = aitOptions()->getRequestedOid('get');
		$val = $this->getValue();
		if(!isset($specialPages[$oid])){
		?>
		<div class="ait-opt-label">
			<?php $this->labelWrapper('', 'inline') ?>
		</div>

		<div class="ait-opt ait-opt-on-off">
			<div class="ait-opt-wrapper">
				<div class="ait-opt-switch">
					<select id="<?php echo $this->getIdAttr(); ?>" name="specific-post[comments]" class="ait-opt-on-off">
						<option <?php if($val == 'open') { ?> selected <?php } ?>  value="open">On</option>
						<option <?php if($val == 'closed') { ?> selected <?php } ?>  value="closed">Off</option>
					</select>
				</div>
			</div>
		</div>

		<?php if($this->helpPosition() != 'label'): ?>
			<div class="ait-opt-help">
				<?php $this->help() ?>
			</div>
		<?php endif; ?>

		<?php
		}
	}

	public function getValue($subKey = '')
	{
		global $post;

		if(!isset($post)){
			$val = get_option('default_comment_status');
		}else{
			$val = $post->comment_status;
		}

		return $val;
	}


}
