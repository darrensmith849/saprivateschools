<?php

namespace WPDeveloper\BetterDocsPro\REST;
use WPDeveloper\BetterDocs\Core\BaseAPI;

class KnowledgeBaseField extends BaseAPI {
    public function register() {
        $this->register_field( 'doc_category', 'knowledge_base', [
            'get_callback' => [$this, 'knowledge_base_collection']
        ] );
    }

    public function knowledge_base_collection( $object ) {
        $knowledgebases = get_term_meta( $object['id'], 'doc_category_knowledge_base', true );
        if ( empty( $knowledgebases ) ) {
            return [];
        }
        return $knowledgebases;
    }
}
