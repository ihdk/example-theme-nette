{if AitWoocommerce::enabled() and !AitWoocommerce::currentPageIs('cart') and !AitWoocommerce::currentPageIs('checkout')}
<div class="ait-woocommerce-cart-widget">
	<div id="ait-woocommerce-cart-wrapper" n:class="AitWoocommerce::cartGetItemsCount() == 0 ? cart-empty, cart-wrapper">
		<div id="ait-woocommerce-cart-header" class="cart-header">
			<svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>

			<span id="ait-woocommerce-cart-info" class="cart-header-info">
				<span id="ait-woocomerce-cart-items-count" class="cart-count">{=AitWoocommerce::cartGetItemsCount()}</span>
			</span>
		</div>
		<div id="ait-woocommerce-cart" class="cart-content" style="display: none">
			{!=AitWoocommerce::cartDisplay()}

			{if AitWoocommerce::cartGetItemsCount() == 0}
				<a href="{get_permalink(AitWoocommerce::getPage('shop'))}" class="ait-button shop">{_('Shop')}</a>
			{/if}
		</div>
	</div>

	<script type="text/javascript">
		jQuery(document).ready(function() {
			var $cart = jQuery('#ait-woocommerce-cart-wrapper');

			if (!$cart.hasClass('cart-empty')) return

			jQuery(document.body).on('added_to_cart', function() {
				$cart.removeClass('cart-empty');
			});
		});
	</script>
</div>
{/if}
