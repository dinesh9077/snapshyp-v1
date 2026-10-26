<script>
	// Copy one courier's B2C rate grid into another. Cells match on weight + zone column heading,
	// because zone ids and slugs differ between couriers (e.g. "mtm" vs "metro_to_metro").
	(function() {
		function rateTable(blockIndex) {
			return $('input[name="shipping[' + blockIndex + '][shipping_company_id]"]').parent().find('table').first();
		}

		// { "0.5|metro to metro|0": input, ... } for every editable rate / extra / COD input of a courier
		function rateCells(blockIndex) {
			var $table = rateTable(blockIndex);
			var headings = $table.find('thead th').map(function() {
				return $(this).text().trim().toLowerCase().replace(/\s+/g, ' ');
			}).get();
			var cells = {};
			$table.find('tbody tr').each(function() {
				var $tds = $(this).children('td');
				var weight = parseFloat($tds.first().find('input').val());
				if (isNaN(weight)) {
					return;
				}
				$tds.each(function(column) {
					if (column === 0) {
						return;
					}
					$(this).find('input[type="number"]').not('[readonly]').each(function(position) {
						cells[weight + '|' + headings[column] + '|' + position] = this;
					});
				});
			});
			return cells;
		}

		$(document).on('click', '.copy-rates-apply', function() {
			var targetIndex = $(this).data('target');
			var $source = $(this).closest('.copy-rates-control').find('.copy-rates-source');
			var sourceIndex = $source.val();
			if (!sourceIndex) {
				toastrMsg('error', 'Please select the courier to copy rates from.');
				return;
			}

			var sourceCells = rateCells(sourceIndex);
			var targetCells = rateCells(targetIndex);
			var copied = 0;
			var missing = 0;
			$.each(targetCells, function(key, input) {
				if (sourceCells[key]) {
					$(input).val(sourceCells[key].value).trigger('change');
					copied++;
				} else {
					missing++;
				}
			});

			var sourceName = $source.find('option:selected').text();
			if (!copied) {
				toastrMsg('error', 'Nothing to copy: ' + sourceName + ' has no matching weights or zones.');
				return;
			}
			toastrMsg('success', 'Copied ' + copied + ' rates from ' + sourceName +
				(missing ? ' (' + missing + ' cells had no match and were left as is)' : '') + '. Click Save to keep them.');
		});
	})();
</script>
