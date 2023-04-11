<?php


class AitPaymentsOptionControl extends AitOptionControl
{

	protected function init()
	{
		$this->isCloneable = true;
	}

	protected function control()
	{
		?>
		<div class="ait-opt-label">
			<?php $this->labelWrapper() ?>
		</div>

		<div class="ait-opt ait-opt-<?php echo $this->id ?>">
			<div class="ait-opt-wrapper chosen-wrapper">
				<?php

				if ( class_exists('AitPaypal') ) {
					$paypal = AitPaypal::getInstance();
					$payments_avalaible = $paypal->payments;
					if(count($payments_avalaible) > 0){
							?>
						<select id="<?php echo $this->getIdAttr(); ?>" name="<?php echo $this->getNameAttr(); ?>" class="ait-opt-<?php echo $this->key ?> chosen">
							<?php
							foreach($payments_avalaible as $index => $payment){
								$name = $payment->name." (".$payment->price." ".$payment->currencyCode.")";
								?>
								<option <?php selected($this->getValue(), "payment-id-".$index); ?> value='payment-id-<?php echo $index ?>'><?php echo $name ?></option>
								<?php
							}
							?>
						</select>
						<?php
					}else{
						_e('There are no payments defined, please add payments first', 'ait-admin');
					}
				}

				?>
			</div>
		</div>

		<?php if($this->helpPosition() == 'inline'): ?>
			<div class="ait-opt-help">
				<?php $this->help() ?>
			</div>
		<?php endif; ?>

		<?php
	}


}
