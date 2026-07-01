<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_section_id_to_nutritional_assessments extends CI_Migration {

    public function up()
    {
        // Use raw SQL – guaranteed to work
        $this->db->query("ALTER TABLE nutritional_assessments ADD section_id INT(11) NULL AFTER section");
        
        $this->db->query("
            UPDATE nutritional_assessments na
            JOIN grade_sections gs 
                ON na.grade_level = gs.grade 
                AND na.section = gs.section 
                AND na.year = gs.year 
                AND na.legislative_district = gs.legislative_district
                AND na.school_district = gs.school_district
            SET na.section_id = gs.id
            WHERE na.section_id IS NULL
        ");
        
        $this->db->query("ALTER TABLE nutritional_assessments ADD INDEX idx_section_id (section_id)");
    }

    public function down()
    {
        $this->dbforge->drop_column('nutritional_assessments', 'section_id');
    }
}