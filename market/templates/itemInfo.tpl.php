<script type="text/javascript" language="JavaScript">
function showImage (itemID) {
	window.open('productImages/' + itemID + '_m.jpg', 'image', 'location=0,directories=0,height=420,width=420,menubar=0,resizable=1,scrollbars=0');
}
</script>
<?php if ($item->image) echo '<a href="javascript:showImage(' . $item->itemID . ')"><img src="productImages/' . $item->itemID . '_s.jpg" class="alignleft" alt="photo of ' . htmlEscape($item->label) . '"/></a>'; ?>
<h4 class="notop"><?= htmlEscape($item->label) ?></h4>
<p><?= htmlEscape($item->description) ?></p>