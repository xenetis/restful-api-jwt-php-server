<?php

/**
 * Class AbstractController
 */
class AbstractController
{
    protected String $table;
    protected ?AbstractModel $model;
    protected ?string $modelName;

    protected PDO $pdo;
    protected ?Object $input = null;

    /**
     * @param PDO $pdo
     * @param ?Object $input
     */
    public function __construct(PDO $pdo, ?object $input = null)
    {
        $this->pdo = $pdo;
        $this->input = $input;

        $this->table = strtolower(str_replace("Controller", "", get_class($this)));

        $modelName = str_replace("Controller", "Model", get_class($this));
        $this->modelName = (class_exists($modelName)) ? $modelName : null;
    }


    /**
     * @return array
     *
     * @url GET /
     * @noauth
     */
    public function root(): array
    {
        return ['VERSION' => getenv('VERSION')];
    }

    /**
     * @return mixed
     */
    public function getAll()
    {
        return $this->search();
    }

    /**
     * @return mixed
     */
    public function count()
    {
        return $this->search([], null, true);
    }

    /**
     * @param string $value
     * @param string $field
     * @return AbstractModel
     */
    public function get(string $value, string $field = 'id'): AbstractModel
    {
        return $this->search([$field => $value], 1);
    }

    /**
     * @param string $id
     * @return void
     */
    public function delete(string $id)
    {
        $element = $this->search(['id' => $id], 1);
        if ($element instanceof $this->modelName) {
            $query = "DELETE FROM " . $this->table . " WHERE id= :id";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute(['id' => $id]);
        }
    }

    /**
     * @param object $object
     * @return void
     */
    public function create(object $object)
    {
        $fields = get_object_vars($object);
        $keys = array_keys($fields);
        $dotKeys = array_keys($fields);
        foreach ($dotKeys as $k => $v) {
            $dotKeys[$k] = ":" . $v;
        }
        $query = "INSERT INTO " . $this->table . " (" . implode(",", $keys) . ") VALUES (" . implode(",", $dotKeys) . ")";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($fields);
    }

    /**
     * @param object $object
     * @return void
     */
    public function update(object $object)
    {
        $fields = get_object_vars($object);
        $keyField = $object->getKeyField();
        $dotKey = $keyField . " = '" . $fields[$keyField] . "'";

        $dotFields = array_keys($fields);
        unset($dotFields[$keyField]);
        foreach ($dotFields as $k => $v) {
            $dotFields[$k] = $v . " = :" . $v;
        }

        $query = "UPDATE " . $this->table . " SET " . implode(" ,", $dotFields) . " WHERE " . $dotKey;
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($fields);
    }

    /**
     * @param array $params
     * @param string|null $limit
     * @param bool $count
     * @return mixed
     */
    public function search(array $params = [], string $limit = null, bool $count = false)
    {
        $select = ($count) ? "count(*)" : "*";
        $query = "SELECT " . $select . " FROM " . $this->table . " ";
        if (count($params)) {
            $query.= " where ";
            foreach ($params as $k => $v) {
                $query.= " " . $k . " =  :" . $k;
            }
        }
        if($limit != null)
            $query.= " limit " . $limit;

        if (count($params)) {
            // With params: prepared statement
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            if ($count) {
                return $stmt->fetch(PDO::FETCH_COLUMN);
            } else {
                if($limit == 1) {
                    $result = $stmt->fetchAll(PDO::FETCH_CLASS, $this->modelName);
                    return (count($result) == 1) ? $result[0]: $result;
                } else {
                    return $stmt->fetchAll(PDO::FETCH_CLASS, $this->modelName);
                }
            }
        } else {
            // Without params: no prepared statement
            if ($count) {
                return $this->pdo->query($query)->fetch(PDO::FETCH_COLUMN);
            } else {
                if($limit == 1) {
                    $result = $this->pdo->query($query)->fetchAll(PDO::FETCH_CLASS, $this->modelName);
                    return (count($result) == 1) ? $result[0]: $result;
                } else {
                    return $this->pdo->query($query)->fetchAll(PDO::FETCH_CLASS, $this->modelName);
                }
            }
        }
    }
}
