<?php
/**
 * Vue admin i18n messages.
 *
 * @package OmoikaneSimpleSalesReports
 */

declare(strict_types=1);

return array(
	// Common.
	'common.save'                          => __( 'Save', 'omoikane-simple-sales-reports' ),
	'common.back'                          => __( 'Back', 'omoikane-simple-sales-reports' ),
	'common.edit'                          => __( 'Edit', 'omoikane-simple-sales-reports' ),
	'common.delete'                        => __( 'Delete', 'omoikane-simple-sales-reports' ),

	// Template management.
	'template.title'                       => __( 'Template Management', 'omoikane-simple-sales-reports' ),
	'template.empty'                       => __( 'No templates found.', 'omoikane-simple-sales-reports' ),
	'template.loading'                     => __( 'Loading...', 'omoikane-simple-sales-reports' ),

	// Template columns.
	'template.column.name'                 => __( 'Name', 'omoikane-simple-sales-reports' ),
	'template.column.version'              => __( 'Version', 'omoikane-simple-sales-reports' ),
	'template.column.type'                 => __( 'Type', 'omoikane-simple-sales-reports' ),

	// Template types.
	'template.type.system'                 => __( 'System template', 'omoikane-simple-sales-reports' ),
	'template.type.user'                   => __( 'User template', 'omoikane-simple-sales-reports' ),

	// Template actions.
	'template.action.duplicate_and_edit'   => __( 'Duplicate and edit', 'omoikane-simple-sales-reports' ),

	// Template form.
	'template.form.name'                   => __( 'Template name', 'omoikane-simple-sales-reports' ),
	'template.form.content'                => __( 'Template content', 'omoikane-simple-sales-reports' ),

	// Template notice.
	'template.notice.saved'                => __( 'Template saved.', 'omoikane-simple-sales-reports' ),
	'template.notice.deleted'              => __( 'Template deleted.', 'omoikane-simple-sales-reports' ),
	'template.notice.duplicated'           => __( 'Template duplicated.', 'omoikane-simple-sales-reports' ),

	// Template validation.
	'template.validation.name_required'    => __( 'Template name is required.', 'omoikane-simple-sales-reports' ),
	'template.validation.content_required' => __( 'Template content is required.', 'omoikane-simple-sales-reports' ),

	// Template confirmations.
	// Translators: 1: Template name.
	'template.confirm.delete'              => __( 'Delete "%1$s"?', 'omoikane-simple-sales-reports' ),

	// Template tooltips.
	'template.tooltip.system_edit'         => __( 'System templates cannot be edited. Duplicate this template to edit it.', 'omoikane-simple-sales-reports' ),
	'template.tooltip.system_delete'       => __( 'System templates cannot be deleted.', 'omoikane-simple-sales-reports' ),

	// Errors.
	'error.unknown'                        => __( 'An unknown error occurred.', 'omoikane-simple-sales-reports' ),
	'error.rest_config_missing'            => __( 'OSSR REST config is missing.', 'omoikane-simple-sales-reports' ),
);
