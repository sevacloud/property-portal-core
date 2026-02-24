<?php
if (!defined('ABSPATH')) exit;

add_action('acf/init', function () {
    if (!function_exists('acf_add_local_field_group')) return;

    /**
     * PROPERTY FIELDS (ACF Free) - UK address structure
     */
    acf_add_local_field_group([
        'key' => 'group_ppc_property_fields',
        'title' => 'Property Fields',
        'fields' => [
            [
                'key' => 'field_ppc_property_address_line_1',
                'label' => 'Address line 1',
                'name' => 'property_address_line_1',
                'type' => 'text',
                'required' => 1,
                'instructions' => 'House number and street (e.g. 12 Rose Street).',
            ],
            [
                'key' => 'field_ppc_property_address_line_2',
                'label' => 'Address line 2',
                'name' => 'property_address_line_2',
                'type' => 'text',
                'required' => 0,
                'instructions' => 'Flat/unit/building name (optional).',
            ],
            [
                'key' => 'field_ppc_property_town',
                'label' => 'Town / City',
                'name' => 'property_town',
                'type' => 'select',
                'required' => 1,
                'choices' => [
                    'Doncaster' => 'Doncaster',
                    'Rotherham' => 'Rotherham',
                ],
                'default_value' => 'Doncaster',
                'allow_null' => 0,
                'ui' => 1,
            ],
            [
                'key' => 'field_ppc_property_county',
                'label' => 'County',
                'name' => 'property_county',
                'type' => 'text',
                'required' => 0,
                'default_value' => 'South Yorkshire',
            ],
            [
                'key' => 'field_ppc_property_postcode',
                'label' => 'Postcode',
                'name' => 'property_postcode',
                'type' => 'text',
                'required' => 1,
                'instructions' => 'UK postcode (e.g. DN1 2AB).',
            ],
            [
                'key' => 'field_ppc_property_code',
                'label' => 'Property Code',
                'name' => 'property_code',
                'type' => 'text',
                'required' => 0,
                'instructions' => 'Internal reference (optional).',
            ],
            [
                'key' => 'field_ppc_property_region',
                'label' => 'Region / Team',
                'name' => 'property_region',
                'type' => 'select',
                'choices' => [
                    'doncaster' => 'Doncaster',
                    'rotherham' => 'Rotherham',
                ],
                'allow_null' => 1,
                'ui' => 1,
                'instructions' => 'Internal grouping for permissions/reporting (optional for now).',
            ],
            [
                'key' => 'field_ppc_property_manager',
                'label' => 'Managed By',
                'name' => 'property_manager',
                'type' => 'select',
                'choices' => [
                    'Together Housing Group' => 'Together Housing Group',
                    'YWCA' => 'YWCA',
                ],
                'allow_null' => 1,
                'ui' => 1,
            ],
            [
                'key' => 'field_ppc_property_status',
                'label' => 'Occupancy Status',
                'name' => 'property_status',
                'type' => 'select',
                'choices' => [
                    'occupied'    => 'Occupied',
                    'vacant'      => 'Vacant',
                    'maintenance' => 'Maintenance',
                ],
                'default_value' => 'occupied',
                'ui' => 1,
                'required' => 1,
            ],
            [
                'key' => 'field_ppc_property_last_health_safety_inspection',
                'label' => 'Date of Last Health and Safety Inspection',
                'name' => 'property_last_health_safety_inspection',
                'type' => 'date_picker',
                'display_format' => 'd/m/Y',
                'return_format' => 'Y-m-d',
                'first_day' => 1,
                'required' => 0,
            ],
            [
                'key' => 'field_ppc_property_last_void_date',
                'label' => 'Last Void Date',
                'name' => 'property_last_void_date',
                'type' => 'date_picker',
                'display_format' => 'd/m/Y',
                'return_format' => 'Y-m-d',
                'first_day' => 1,
                'required' => 0,
            ],
            [
                'key' => 'field_ppc_property_main_photo',
                'label' => 'Main Photo',
                'name' => 'property_main_photo',
                'type' => 'image',
                'return_format' => 'array',
                'preview_size' => 'medium',
                'library' => 'all',
                'required' => 0,
            ],
            [
                'key' => 'field_ppc_property_notes',
                'label' => 'Internal Notes',
                'name' => 'property_notes',
                'type' => 'textarea',
                'required' => 0,
                'new_lines' => 'br',
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'ppm_property',
                ],
            ],
        ],
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
    ]);

    /**
     * VOID FIELDS (ACF Free)
     */
    acf_add_local_field_group([
        'key' => 'group_ppc_void_fields',
        'title' => 'Void Fields',
        'fields' => [
            [
                'key' => 'field_ppc_void_property',
                'label' => 'Property',
                'name' => 'void_property',
                'type' => 'post_object',
                'post_type' => ['ppm_property'],
                'required' => 1,
                'return_format' => 'object',
                'ui'   => 0,
                //'ajax' => 0,
            ],
            [
                'key' => 'field_ppc_void_start_date',
                'label' => 'Void Start Date',
                'name' => 'void_start_date',
                'type' => 'date_picker',
                'required' => 1,
                'return_format' => 'Y-m-d',
            ],
            [
                'key' => 'field_ppc_void_target_date',
                'label' => 'Target Handover Date',
                'name' => 'void_target_date',
                'type' => 'date_picker',
                'required' => 1,
                'return_format' => 'Y-m-d',
            ],
            [
                'key' => 'field_ppc_void_stage',
                'label' => 'Stage',
                'name' => 'void_stage',
                'type' => 'select',
                'choices' => [
                    'inspection' => 'Inspection',
                    'works'      => 'Works in progress',
                    'cleaning'   => 'Cleaning',
                    'safety'     => 'Safety checks',
                    'ready'      => 'Ready to let',
                    'completed'  => 'Completed',
                ],
                'default_value' => 'inspection',
                'ui' => 1,
            ],
            [
                'key' => 'field_ppc_void_main_photo',
                'label' => 'Main Photo',
                'name' => 'void_main_photo',
                'type' => 'image',
                'return_format' => 'array',
                'preview_size' => 'medium',
                'library' => 'all',
                'required' => 0,
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'ppm_void',
                ],
            ],
        ],
    ]);

    /**
     * REPAIR FIELDS (ACF Free)
     */
    acf_add_local_field_group([
        'key' => 'group_ppc_repair_fields',
        'title' => 'Repair Fields',
        'fields' => [
            [
                'key' => 'field_ppc_repair_summary',
                'label' => 'Repair Summary',
                'name' => 'repair_summary',
                'type' => 'text',
                'required' => 1,
            ],
            [
                'key' => 'field_ppc_repair_property',
                'label' => 'Property',
                'name' => 'repair_property',
                'type' => 'post_object',
                'post_type' => ['ppm_property'],
                'return_format' => 'object',
                'ui' => 0,
                //'ajax' => 0,
                'required' => 1,
            ],
            [
                'key' => 'field_ppc_repair_void',
                'label' => 'Void (optional)',
                'name' => 'repair_void',
                'type' => 'post_object',
                'post_type' => ['ppm_void'],
                'return_format' => 'object',
                'ui' => 0,
                'required' => 0,
                'instructions' => 'Select only if this repair belongs to a void period.',
            ],
            [
                'key' => 'field_ppc_repair_category',
                'label' => 'Category',
                'name' => 'repair_category',
                'type' => 'select',
                'choices' => [
                    'plumbing'   => 'Plumbing',
                    'electrical' => 'Electrical',
                    'heating'    => 'Heating',
                    'structural' => 'Structural',
                    'cleaning'   => 'Cleaning',
                    'safety'     => 'Safety',
                    'other'      => 'Other',
                ],
                'allow_null' => 1,
                'ui' => 1,
            ],
            [
                'key' => 'field_ppc_repair_priority',
                'label' => 'Priority',
                'name' => 'repair_priority',
                'type' => 'select',
                'choices' => [
                    'routine'   => 'Routine',
                    'urgent'    => 'Urgent',
                    'emergency' => 'Emergency',
                ],
                'default_value' => 'routine',
                'ui' => 1,
                'required' => 1,
            ],
            [
                'key' => 'field_ppc_repair_status',
                'label' => 'Status',
                'name' => 'repair_status',
                'type' => 'select',
                'choices' => [
                    'new'          => 'New',
                    'triaged'      => 'Triaged',
                    'assigned'     => 'Assigned',
                    'in_progress'  => 'In progress',
                    'waiting_parts'=> 'Waiting (parts)',
                    'complete'     => 'Complete',
                    'cancelled'    => 'Cancelled',
                ],
                'default_value' => 'new',
                'ui' => 1,
                'required' => 1,
            ],
            [
                'key' => 'field_ppc_repair_owner',
                'label' => 'Project Worker',
                'name' => 'repair_owner',
                'type' => 'user',
                'role' => ['staff', 'administrator'],
                'allow_null' => 1,
                'multiple' => 0,
                'return_format' => 'array',
            ],
            [
                'key' => 'field_ppc_repair_responsibility',
                'label' => 'Responsibility',
                'name' => 'repair_responsibility',
                'type' => 'select',
                'choices' => [
                    'ywca' => 'YWCA',
                    'together_housing_group' => 'Together Housing Group',
                    'external_contractor' => 'External Contractor',
                ],
                'allow_null' => 1,
                'ui' => 1,
            ],
            [
                'key' => 'field_ppc_repair_external_contractor_name',
                'label' => 'External Contractor Name',
                'name' => 'repair_external_contractor_name',
                'type' => 'text',
                'required' => 1,
                'conditional_logic' => [
                    [
                        [
                            'field' => 'field_ppc_repair_responsibility',
                            'operator' => '==',
                            'value' => 'external_contractor',
                        ],
                    ],
                ],
            ],
            [
                'key' => 'field_ppc_repair_external_contractor_phone',
                'label' => 'External Contractor Phone Number',
                'name' => 'repair_external_contractor_phone',
                'type' => 'text',
                'required' => 1,
                'conditional_logic' => [
                    [
                        [
                            'field' => 'field_ppc_repair_responsibility',
                            'operator' => '==',
                            'value' => 'external_contractor',
                        ],
                    ],
                ],
            ],
            [
                'key' => 'field_ppc_repair_due_date',
                'label' => 'Target Completion Date',
                'name' => 'repair_due_date',
                'type' => 'date_picker',
                'required' => 0,
                'return_format' => 'Y-m-d',
            ],
            [
                'key' => 'field_ppc_repair_completed_date',
                'label' => 'Completed Date',
                'name' => 'repair_completed_date',
                'type' => 'date_picker',
                'required' => 0,
                'return_format' => 'Y-m-d',
            ],
            [
                'key' => 'field_ppc_repair_main_photo',
                'label' => 'Main Photo',
                'name' => 'repair_main_photo',
                'type' => 'image',
                'return_format' => 'array',
                'preview_size' => 'medium',
                'library' => 'all',
                'required' => 0,
            ],
            [
                'key' => 'field_ppc_repair_internal_notes',
                'label' => 'Internal Notes',
                'name' => 'repair_internal_notes',
                'type' => 'textarea',
                'required' => 0,
                'new_lines' => 'br',
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'ppm_repair',
                ],
            ],
        ],
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
    ]);

    /**
     * TENANT FIELDS (ACF Free)
     */
    acf_add_local_field_group([
        'key' => 'group_ppc_tenant_fields',
        'title' => 'Tenant Details',
        'location' => [[[
            'param' => 'post_type',
            'operator' => '==',
            'value' => 'ppm_tenant',
        ]]],
        'fields' => [
            [
                'key' => 'field_ppc_tenant_name',
                'label' => 'Name',
                'name' => 'tenant_name',
                'type' => 'text',
                'required' => 1,
            ],
            [
                'key' => 'field_ppc_tenant_phone',
                'label' => 'Phone Number',
                'name' => 'tenant_phone',
                'type' => 'text',
                'required' => 1,
            ],
            [
                'key' => 'field_ppc_tenant_dob',
                'label' => 'Date of Birth',
                'name' => 'tenancy_dob',
                'type' => 'date_picker',
                'return_format' => 'Y-m-d',
                'required' => 1,
            ],
            [
                'key' => 'field_ppc_tenant_email',
                'label' => 'Email',
                'name' => 'tenant_email',
                'type' => 'email',
                'required' => 0,
            ],
            [
                'key' => 'field_ppc_tenant_children_count',
                'label' => 'Number of Children',
                'name' => 'tenant_children_count',
                'type' => 'number',
                'required' => 1,
                'min' => 0,
                'step' => 1,
                'instructions' => 'Enter the total number of children living at the property.',
            ],
            [
                'key' => 'field_ppc_tenant_notes',
                'label' => 'Notes',
                'name' => 'tenant_notes',
                'type' => 'textarea',
                'required' => 0,
            ],
            [
                // Update to repeater field in pro
                'key' => 'field_ppc_tenant_document',
                'label' => 'Tenant Document',
                'name' => 'tenant_document',
                'type' => 'file',
                'required' => 0,
                'return_format' => 'id', // IMPORTANT
                'library' => 'all',
                'mime_types' => 'pdf,doc,docx,jpg,jpeg,png',
                'instructions' => 'Upload supporting document (ID, agreement, etc).',
            ],
        ],
    ]);

    /**
     * TENANCY FIELDS (ACF Free)
     */
    acf_add_local_field_group([
        'key' => 'group_ppc_tenancy_fields',
        'title' => 'Tenancy',
        'location' => [[[
            'param' => 'post_type',
            'operator' => '==',
            'value' => 'ppm_tenancy',
        ]]],
        'fields' => [
            [
                'key' => 'field_ppc_tenancy_property',
                'label' => 'Property',
                'name' => 'tenancy_property',
                'type' => 'post_object',
                'post_type' => ['ppm_property'],
                'return_format' => 'id',
                'required' => 1,
            ],
            [
                'key' => 'field_ppc_tenancy_tenant',
                'label' => 'Tenant',
                'name' => 'tenancy_tenant',
                'type' => 'post_object',
                'post_type' => ['ppm_tenant'],
                'return_format' => 'id',
                'required' => 1,
            ],
            [
                'key' => 'field_ppc_tenancy_start',
                'label' => 'Start Date',
                'name' => 'tenancy_start',
                'type' => 'date_picker',
                'return_format' => 'Y-m-d',
                'required' => 1,
            ],
            [
                'key' => 'field_ppc_tenancy_end',
                'label' => 'End Date',
                'name' => 'tenancy_end',
                'type' => 'date_picker',
                'return_format' => 'Y-m-d',
                'required' => 0,
                'instructions' => 'Leave blank for current tenancy.',
            ],
            [
                'key' => 'field_ppc_tenancy_notes',
                'label' => 'Notes',
                'name' => 'tenancy_notes',
                'type' => 'textarea',
                'required' => 0,
            ],
        ],
        ]);
});
