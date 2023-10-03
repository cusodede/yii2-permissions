/*jshint esversion: 11 */
/*jshint strict: false */

/* globals $ */
$(document).on('click', '#copy-permission-btn', function() {
	const selectedOption = $('#copy-permission-select option:selected');
	const permissionIds = $(selectedOption).data('permissionids');
	const collectionIds = $(selectedOption).data('collectionids');

	$('#permissionscollections-relatedpermissions').val(permissionIds);
	$('#permissionscollections-relatedslavepermissionscollections').val(collectionIds);

	$('#permissionscollections-relatedpermissions').multiSelect();
	$('#permissionscollections-relatedslavepermissionscollections').multiSelect();
});
