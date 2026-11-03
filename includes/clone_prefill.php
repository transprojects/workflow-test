<?php
/* Clone support for the master add pages.
   <route>/clone-xxx/<id>  ->  add page with ?clone=<id>: the source row is loaded and its values pre-fill the add form;
   saving inserts a NEW record (the add page's own insert code runs, plus a duplicate check for clones). */

/* source row for ?clone=<id>, or null */
function clone_row($db, $table, $pk)
{
	if (!isset($_REQUEST['clone'])) {
		return null;
	}
	$res = $db->query("SELECT * FROM `".$table."` WHERE `".$pk."` = ".(int)$_REQUEST['clone']);
	return ($res && $res->num_rows > 0) ? $res->fetch_assoc() : null;
}

/* fills the form fields by name: $values = array(field name => value | array of values).
   Call near the end of the page (the form is already in the DOM): it fills at once, so scripts that read the fields on
   DOM-ready see the values, and again after DOM-ready so select plugins refresh. */
function clone_prefill($values)
{
	if (!$values) {
		return;
	}
	$json = json_encode($values, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_PARTIAL_OUTPUT_ON_ERROR);
	?>
<script>
(function ($) {
	var v = <?php echo $json; ?>;
	function fill() {
		$.each(v, function (name, val) {
			if (val === null) { return; }
			$('[name="' + name + '"]').not('[type=hidden],[type=file]').each(function () {
				var $el = $(this), t = (this.type || '').toLowerCase();
				if (t === 'checkbox' || t === 'radio') {
					this.checked = $.inArray(this.value, $.map($.isArray(val) ? val : [val], String)) > -1;
					return;
				}
				$el.val(val);
				try { $el.trigger('chosen:updated'); } catch (e) {}
				try { if ($el.data('select2')) { $el.trigger('change.select2'); } } catch (e) {}
				try { if ($el.data('multiselect')) { $el.multiselect('refresh'); } } catch (e) {}
			});
		});
	}
	fill();
	$(function () { setTimeout(fill, 0); });
})(jQuery);
</script>
<?php
}
