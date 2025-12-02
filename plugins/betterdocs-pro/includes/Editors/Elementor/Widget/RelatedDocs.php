<?php

namespace WPDeveloper\BetterDocsPro\Editors\Elementor\Widget;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Typography;
use WPDeveloper\Betterdocs\Editors\Elementor\BaseWidget;

class RelatedDocs extends BaseWidget {

    public function get_name() {
        return 'betterdocs-related-docs';
    }
    public function get_icon() {
        return 'betterdocs-icon-related-docs';
    }

    public function get_categories() {
        return ['betterdocs-elements-single'];
    }

    public function get_keywords() {
        return ['betterdocs-elements', 'related', 'betterdocs', 'docs', 'related-docs'];
    }

    public function get_title() {
        return __( 'BetterDocs Related Docs', 'betterdocs-pro' );
    }

    public function get_style_depends() {
        return ['single-doc-related-articles'];
    }

    protected function register_controls() {
        $this->start_controls_section(
            'section_related_docs_controls',
            [
                'label' => __( 'Controls', 'betterdocs-pro' )
            ]
        );

        $this->add_control(
            'related_docs_heading',
            [
                'label'   => __( 'Heading', 'betterdocs-pro' ),
                'type'    => Controls_Manager::TEXT,
                'default' => esc_html__( 'Related Docs', 'betterdocs-pro' )
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section( 'section_related_docs_box', [
            'label' => __( 'Wrapper Box', 'betterdocs' ),
            'tab'   => Controls_Manager::TAB_STYLE
        ] );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'     => 'related_docs_box_background',
                'types'    => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .betterdocs-related-articles-container-front'
            ]
        );

        $this->add_responsive_control(
            'related_docs_box_padding',
            [
                'label'      => __( 'Padding', 'betterdocs-pro' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-related-articles-container-front' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]
            ]
        );

        $this->add_responsive_control(
            'related_docs_box_margin',
            [
                'label'      => __( 'Margin', 'betterdocs-pro' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .betterdocs-related-articles-container-front' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_related_docs_heading',
            [
                'label' => __( "Heading", "betterdocs-pro" ),
                'tab'   => Controls_Manager::TAB_STYLE
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'     => 'section_related_docs_background_heading',
                'types'    => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .related-articles-title'
            ]
        );

        $this->add_control(
            'section_related_docs_heading_color',
            [
                'label'     => esc_html__( 'Color', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .related-articles-title' => 'color: {{VALUE}};'
                ]
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'section_related_docs_heading_typography',
                'selector' => '{{WRAPPER}} .related-articles-title'
            ]
        );

        $this->add_responsive_control(
            'section_related_docs_heading_padding',
            [
                'label'      => __( 'Padding', 'betterdocs-pro' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .related-articles-title' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]
            ]
        );

        $this->add_responsive_control(
            'section_related_docs_heading_margin',
            [
                'label'      => __( 'Margin', 'betterdocs-pro' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .related-articles-title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section( 'section_related_docs_list', [
            'label' => __( 'List', 'betterdocs' ),
            'tab'   => Controls_Manager::TAB_STYLE
        ] );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'     => 'section_related_docs_list_background',
                'types'    => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .related-articles-list li'
            ]
        );

        $this->add_control(
            'section_related_docs_list_color',
            [
                'label'     => esc_html__( 'Color', 'betterdocs' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .related-articles-list li a' => 'color: {{VALUE}};'
                ]
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'section_related_docs_list_typography',
                'selector' => '{{WRAPPER}} .related-articles-list li a'
            ]
        );

        $this->add_responsive_control(
            'section_related_docs_list_padding',
            [
                'label'      => __( 'Padding', 'betterdocs-pro' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .related-articles-list li' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]
            ]
        );

        $this->add_responsive_control(
            'section_related_docs_list_margin',
            [
                'label'      => __( 'Margin', 'betterdocs-pro' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .related-articles-list li' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]
            ]
        );

        $this->end_controls_section();
    }

    protected function render_callback() {
        $this->views( 'widgets/related-docs' );
    }
}
