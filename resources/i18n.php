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

	// Branding settings.
	'branding.title'                       => __( 'Branding Images', 'omoikane-simple-sales-reports' ),
	'branding.logo'                        => __( 'Logo', 'omoikane-simple-sales-reports' ),
	'branding.stamp'                       => __( 'Company Stamp', 'omoikane-simple-sales-reports' ),
	'branding.limits'                      => __( 'PNG, JPEG, or WebP. Up to 1 MiB, 4,096 px per side, and 4,194,304 pixels in total. Static images only.', 'omoikane-simple-sales-reports' ),
	'branding.empty'                       => __( 'No image registered.', 'omoikane-simple-sales-reports' ),
	'branding.choose'                      => __( 'Image file', 'omoikane-simple-sales-reports' ),
	'branding.preview_saved'               => __( 'Saved image', 'omoikane-simple-sales-reports' ),
	'branding.preview_unsaved'             => __( 'Unsaved preview', 'omoikane-simple-sales-reports' ),
	'branding.cancel_selection'            => __( 'Discard selection', 'omoikane-simple-sales-reports' ),
	'branding.register'                    => __( 'Register', 'omoikane-simple-sales-reports' ),
	'branding.replace'                     => __( 'Replace', 'omoikane-simple-sales-reports' ),
	'branding.retry'                       => __( 'Retry', 'omoikane-simple-sales-reports' ),
	'branding.confirm_delete'              => __( 'Delete this image?', 'omoikane-simple-sales-reports' ),
	'branding.saved'                       => __( 'Image saved.', 'omoikane-simple-sales-reports' ),
	'branding.deleted'                     => __( 'Image deleted.', 'omoikane-simple-sales-reports' ),
	'branding.read_failed'                 => __( 'The image file could not be read.', 'omoikane-simple-sales-reports' ),
	'branding.too_large'                   => __( 'The image file must be 1 MiB or smaller.', 'omoikane-simple-sales-reports' ),

	// Errors.
	'error.unknown'                        => __( 'An unknown error occurred.', 'omoikane-simple-sales-reports' ),
	'error.rest_config_missing'            => __( 'OSSR REST config is missing.', 'omoikane-simple-sales-reports' ),
);
