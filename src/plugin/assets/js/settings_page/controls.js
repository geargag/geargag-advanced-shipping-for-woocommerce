import "jquery.repeater";

$(".repeat-table").repeater({
	hide: function(deleteElement) {
		if (confirm("Are you sure you want to delete this element?")) {
			$(this).slideUp(deleteElement);
		}
	},
});

$(".drag")
	.sortable({
		axis: "y",
		cursor: "pointer",
		opacity: 0.5,
		placeholder: "row-dragging",
		delay: 150,
	})
	.disableSelection();
