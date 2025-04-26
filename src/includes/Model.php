<?php
/**
 * Base Model Class
 * 
 * Provides common functionality for all models:
 * - Database operations
 * - Validation
 * - Error handling
 * - Timestamps
 * - Soft deletes
 */
abstract class Model {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $guarded = ['id'];
    protected $timestamps = true;
    protected $softDelete = true;
    protected $errors = [];
    protected $data = [];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get table name
     */
    public function getTable() {
        if (!$this->table) {
            // Convert class name to snake case for table name
            $class = get_class($this);
            $this->table = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $class));
        }
        return $this->table;
    }
    
    /**
     * Find record by ID
     */
    public function find($id) {
        $where = $this->softDelete ? 
                 "{$this->primaryKey} = ? AND deleted_at IS NULL" : 
                 "{$this->primaryKey} = ?";
        
        return $this->db->fetchOne(
            "SELECT * FROM {$this->getTable()} WHERE {$where}",
            [$id]
        );
    }
    
    /**
     * Find all records
     */
    public function findAll($where = '', $params = [], $orderBy = '', $limit = '') {
        if ($this->softDelete) {
            $where = $where ? 
                     "({$where}) AND deleted_at IS NULL" : 
                     "deleted_at IS NULL";
        }
        
        return $this->db->select(
            $this->getTable(),
            '*',
            $where,
            $params,
            $orderBy,
            $limit
        );
    }
    
    /**
     * Create new record
     */
    public function create($data) {
        $this->data = $data;
        
        if (!$this->validate()) {
            return false;
        }
        
        $data = $this->filterData($data);
        
        if ($this->timestamps) {
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        
        try {
            $this->db->beginTransaction();
            
            $id = $this->db->insertInto($this->getTable(), $data);
            
            if ($this->afterCreate($id, $data) === false) {
                $this->db->rollback();
                return false;
            }
            
            $this->db->commit();
            return $id;
            
        } catch (Exception $e) {
            $this->db->rollback();
            $this->errors[] = $e->getMessage();
            return false;
        }
    }
    
    /**
     * Update record
     */
    public function update($id, $data) {
        $this->data = $data;
        
        if (!$this->validate()) {
            return false;
        }
        
        $data = $this->filterData($data);
        
        if ($this->timestamps) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        
        try {
            $this->db->beginTransaction();
            
            $where = $this->softDelete ? 
                     "{$this->primaryKey} = ? AND deleted_at IS NULL" : 
                     "{$this->primaryKey} = ?";
            
            $affected = $this->db->updateTable(
                $this->getTable(),
                $data,
                $where,
                [$id]
            );
            
            if ($affected && $this->afterUpdate($id, $data) === false) {
                $this->db->rollback();
                return false;
            }
            
            $this->db->commit();
            return $affected;
            
        } catch (Exception $e) {
            $this->db->rollback();
            $this->errors[] = $e->getMessage();
            return false;
        }
    }
    
    /**
     * Delete record
     */
    public function delete($id) {
        try {
            $this->db->beginTransaction();
            
            if ($this->softDelete) {
                $affected = $this->db->softDelete(
                    $this->getTable(),
                    "{$this->primaryKey} = ?",
                    [$id]
                );
            } else {
                $affected = $this->db->deleteFrom(
                    $this->getTable(),
                    "{$this->primaryKey} = ?",
                    [$id]
                );
            }
            
            if ($affected && $this->afterDelete($id) === false) {
                $this->db->rollback();
                return false;
            }
            
            $this->db->commit();
            return $affected;
            
        } catch (Exception $e) {
            $this->db->rollback();
            $this->errors[] = $e->getMessage();
            return false;
        }
    }
    
    /**
     * Filter data based on fillable/guarded properties
     */
    protected function filterData($data) {
        $filtered = [];
        
        if (!empty($this->fillable)) {
            foreach ($this->fillable as $field) {
                if (isset($data[$field])) {
                    $filtered[$field] = $data[$field];
                }
            }
        } else {
            $filtered = $data;
            foreach ($this->guarded as $field) {
                unset($filtered[$field]);
            }
        }
        
        return $filtered;
    }
    
    /**
     * Validate data
     */
    protected function validate() {
        return true;
    }
    
    /**
     * After create hook
     */
    protected function afterCreate($id, $data) {
        return true;
    }
    
    /**
     * After update hook
     */
    protected function afterUpdate($id, $data) {
        return true;
    }
    
    /**
     * After delete hook
     */
    protected function afterDelete($id) {
        return true;
    }
    
    /**
     * Get validation errors
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Add validation error
     */
    protected function addError($error) {
        $this->errors[] = $error;
    }
    
    /**
     * Clear validation errors
     */
    protected function clearErrors() {
        $this->errors = [];
    }
    
    /**
     * Check if field value is unique
     */
    protected function isUnique($field, $value, $exceptId = null) {
        $where = "{$field} = ?";
        $params = [$value];
        
        if ($exceptId !== null) {
            $where .= " AND {$this->primaryKey} != ?";
            $params[] = $exceptId;
        }
        
        if ($this->softDelete) {
            $where .= " AND deleted_at IS NULL";
        }
        
        return !$this->db->exists($this->getTable(), $where, $params);
    }
    
    /**
     * Get field label
     */
    protected function getFieldLabel($field) {
        return ucwords(str_replace('_', ' ', $field));
    }
    
    /**
     * Required field validation
     */
    protected function validateRequired($field) {
        if (empty($this->data[$field])) {
            $this->addError($this->getFieldLabel($field) . ' is required');
            return false;
        }
        return true;
    }
    
    /**
     * Email validation
     */
    protected function validateEmail($field) {
        if (!empty($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->addError($this->getFieldLabel($field) . ' must be a valid email address');
            return false;
        }
        return true;
    }
    
    /**
     * Numeric validation
     */
    protected function validateNumeric($field) {
        if (!empty($this->data[$field]) && !is_numeric($this->data[$field])) {
            $this->addError($this->getFieldLabel($field) . ' must be a number');
            return false;
        }
        return true;
    }
    
    /**
     * Date validation
     */
    protected function validateDate($field) {
        if (!empty($this->data[$field])) {
            $date = date_parse($this->data[$field]);
            if ($date['error_count'] > 0) {
                $this->addError($this->getFieldLabel($field) . ' must be a valid date');
                return false;
            }
        }
        return true;
    }
    
    /**
     * Length validation
     */
    protected function validateLength($field, $min = null, $max = null) {
        if (!empty($this->data[$field])) {
            $length = strlen($this->data[$field]);
            
            if ($min !== null && $length < $min) {
                $this->addError($this->getFieldLabel($field) . " must be at least {$min} characters");
                return false;
            }
            
            if ($max !== null && $length > $max) {
                $this->addError($this->getFieldLabel($field) . " must not exceed {$max} characters");
                return false;
            }
        }
        return true;
    }
    
    /**
     * Range validation
     */
    protected function validateRange($field, $min = null, $max = null) {
        if (!empty($this->data[$field])) {
            if ($min !== null && $this->data[$field] < $min) {
                $this->addError($this->getFieldLabel($field) . " must be at least {$min}");
                return false;
            }
            
            if ($max !== null && $this->data[$field] > $max) {
                $this->addError($this->getFieldLabel($field) . " must not exceed {$max}");
                return false;
            }
        }
        return true;
    }
    
    /**
     * Pattern validation
     */
    protected function validatePattern($field, $pattern, $message) {
        if (!empty($this->data[$field]) && !preg_match($pattern, $this->data[$field])) {
            $this->addError($this->getFieldLabel($field) . ' ' . $message);
            return false;
        }
        return true;
    }
}
