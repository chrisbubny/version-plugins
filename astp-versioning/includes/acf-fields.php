<?php
/**
 * Register ACF fields for version metadata
 *
 * @package ASTP_Versioning
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register ACF fields for version metadata
 */
function astp_register_acf_fields() {
    if (function_exists('acf_add_local_field_group')) {
        // Version Fields
        acf_add_local_field_group([
            'key' => 'group_astp_version',
            'title' => 'Version Information',
            'fields' => [
                [
                    'key' => 'field_version_number',
                    'label' => 'Version Number',
                    'name' => 'version_number',
                    'type' => 'text',
                    'required' => 1,
                ],
                [
                    'key' => 'field_version_date',
                    'label' => 'Release Date',
                    'name' => 'release_date',
                    'type' => 'date_picker',
                    'required' => 1,
                    'display_format' => 'F j, Y',
                    'return_format' => 'Y-m-d',
                ],
                [
                    'key' => 'field_version_type',
                    'label' => 'Version Type',
                    'name' => 'version_type',
                    'type' => 'select',
                    'choices' => [
                        'major' => 'Major',
                        'minor' => 'Minor',
                    ],
                    'required' => 1,
                ],
                [
                    'key' => 'field_github_link',
                    'label' => 'GitHub Link',
                    'name' => 'github_link',
                    'type' => 'url',
                ],
                [
                    'key' => 'field_pdf_file',
                    'label' => 'PDF Document',
                    'name' => 'pdf_file',
                    'type' => 'file',
                    'return_format' => 'array',
                    'mime_types' => 'pdf',
                ],
                [
                    'key' => 'field_word_file',
                    'label' => 'Word Document',
                    'name' => 'word_file',
                    'type' => 'file',
                    'return_format' => 'array',
                    'mime_types' => 'doc,docx',
                ],
                [
                    'key' => 'field_parent_content',
                    'label' => 'Parent Content',
                    'name' => 'parent_content',
                    'type' => 'post_object',
                    'post_type' => ['astp_test_method'],
                    'required' => 1,
                ],
                [
                    'key' => 'field_previous_version',
                    'label' => 'Previous Version',
                    'name' => 'previous_version',
                    'type' => 'post_object',
                    'post_type' => ['astp_version'],
                ],
            ],
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'astp_version',
                    ],
                ],
            ],
        ]);
        
        // CCG & TP Relationship to Version
        acf_add_local_field_group([
            'key' => 'group_astp_content_versions',
            'title' => 'Content Versions',
            'fields' => [
                [
                    'key' => 'field_current_version',
                    'label' => 'Current Version',
                    'name' => 'current_version',
                    'type' => 'post_object',
                    'post_type' => ['astp_version'],
                    'return_format' => 'id',
                ],
                [
                    'key' => 'field_versions',
                    'label' => 'All Versions',
                    'name' => 'versions',
                    'type' => 'relationship',
                    'post_type' => ['astp_version'],
                    'filters' => ['search'],
                    'return_format' => 'id',
                ],
                [
                    'key' => 'field_in_development_version',
                    'label' => 'Version In Development',
                    'name' => 'in_development_version',
                    'type' => 'post_object',
                    'post_type' => ['astp_version'],
                    'return_format' => 'id',
                ],
            ],
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'astp_test_method',
                    ],
                ],
            ],
        ]);
        
        // Changelog Details
        acf_add_local_field_group([
            'key' => 'group_astp_changelog',
            'title' => 'Change Log Details',
            'fields' => [
                [
                    'key' => 'field_changelog_version',
                    'label' => 'Associated Version',
                    'name' => 'changelog_version',
                    'type' => 'post_object',
                    'post_type' => ['astp_version'],
                    'return_format' => 'id',
                    'required' => 1,
                ],
                [
                    'key' => 'field_changelog_changes',
                    'label' => 'Change Details',
                    'name' => 'changelog_changes',
                    'type' => 'repeater',
                    'layout' => 'block',
                    'button_label' => 'Add Change',
                    'sub_fields' => [
                        [
                            'key' => 'field_change_type',
                            'label' => 'Change Type',
                            'name' => 'change_type',
                            'type' => 'select',
                            'choices' => [
                                'addition' => 'Addition',
                                'removal' => 'Removal',
                                'amendment' => 'Amendment',
                            ],
                            'required' => 1,
                        ],
                        [
                            'key' => 'field_change_description',
                            'label' => 'Description',
                            'name' => 'description',
                            'type' => 'textarea',
                            'rows' => 3,
                            'required' => 1,
                        ],
                        [
                            'key' => 'field_change_author',
                            'label' => 'Author',
                            'name' => 'author',
                            'type' => 'user',
                            'return_format' => 'id',
                        ],
                    ],
                ],
            ],
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'astp_changelog',
                    ],
                ],
            ],
        ]);
    }
}
add_action('acf/init', 'astp_register_acf_fields'); 