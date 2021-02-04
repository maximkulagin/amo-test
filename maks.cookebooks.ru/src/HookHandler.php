<?php

use AmoCRM\Collections\CustomFieldsValuesCollection;
use AmoCRM\Models\CustomFieldsValues\NumericCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\NumericCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueModels\NumericCustomFieldValueModel;
use AmoCRM\Models\LeadModel;
use AmoCRM\Client\AmoCRMApiClient;

/**
 * Class HookHandler
 */
class HookHandler
{
    protected AmoCRMApiClient $apiClient;
    protected array $post;
    protected ?LeadModel $handledLead;

    /**
     * @return AmoCRMApiClient
     */
    public function getApiClient(): AmoCRMApiClient
    {
        return $this->apiClient;
    }

    /**
     * @return array
     */
    public function getPost(): array
    {
        return $this->post;
    }

    /**
     * @return LeadModel|null
     */
    public function getHandledLead(): ?LeadModel
    {
        return $this->handledLead;
    }

    /**
     * @param AmoCRMApiClient $apiClient
     */
    public function setApiClient(AmoCRMApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    /**
     * @param array $post
     */
    public function setPost(array $post): void
    {
        $this->post = $post;
    }

    /**
     * @param LeadModel|null $handledLead
     */
    public function setHandledLead(?LeadModel $handledLead)
    {
        $this->handledLead = $handledLead;
    }

    /**
     * HookHandler constructor.
     * @param AmoCRMApiClient $apiClient
     * @param array $post
     */
    public function __construct(AmoCRMApiClient $apiClient, array $post)
    {
        $this->apiClient = $apiClient;
        $this->post = $post;
        $info = null;
        //если сделка обновлена
        if (isset($post["leads"]["update"][0])) {
            $info = $post["leads"]["update"][0];
        } //если сделка новая
        else {
            if (isset($post["leads"]["add"][0])) {
                $info = $post["leads"]["add"][0];
            }
        }
        if ($info) {
            try {
                $this->handledLead = $apiClient->leads()->getOne($info["id"]);
            } catch (Exception $e) {
                echo $e;
            }
        }
    }

    /**
     *
     */
    public function sendLead()
    {
        if ($this->handledLead) {
            try {
                $this->apiClient->leads()->updateOne($this->handledLead);
            } catch (Exception $e) {
                echo $e;
            }
        }
    }

    /**
     * @param $fieldId1
     * @param $fieldId2
     * @param $field2Name
     * @return LeadModel|null
     */
    public function doubleField($fieldId1, $fieldId2, $field2Name): ?LeadModel
    {
        if ($this->handledLead) {
            $customFields = $this->handledLead->getCustomFieldsValues();
            $customFieldValueModel = $customFields->getBy('fieldId', $fieldId1);
            $field1 = $customFieldValueModel->getValues()->first();
            $value1 = floatval($field1->toArray()['value']);
            $value2 = $value1 * 2;

            $leadCustomFieldsCollection = new CustomFieldsValuesCollection();
            $numericCustomFieldValueModel = new NumericCustomFieldValuesModel();
            $numericCustomFieldValueModel->setFieldId($fieldId2);
            $numericCustomFieldValueModel->setFieldName($field2Name);
            $numericCustomFieldValueModel->setValues(
                (new NumericCustomFieldValueCollection())
                    ->add((new NumericCustomFieldValueModel())->setValue($value2))
            );
            $leadCustomFieldsCollection->add($numericCustomFieldValueModel);
            $leadCustomFieldsCollection->add($customFieldValueModel);
            $this->handledLead->setCustomFieldsValues($leadCustomFieldsCollection);
            return $this->handledLead;
        } else
            return null;
    }

    /**
     * @param $fieldId
     * @return bool
     */
    public function isContainsField($fieldId): bool
    {
        if ($this->handledLead) {
            $customFields = $this->handledLead->getCustomFieldsValues();
            if (!$customFields)
                return false;
            else {
                if ($customFields->getBy('fieldId', $fieldId)) {
                    return true;
                } else
                    return false;
            }
        }
        return false;
    }

    /**
     * @param $fieldId1
     * @param $fieldId2
     * @return bool
     */
    public function isCalculated($fieldId1, $fieldId2): bool
    {
        if ($this->handledLead) {
            $customFields = $this->handledLead->getCustomFieldsValues();
            if ($customFields) {
                $field1 = $customFields->getBy('fieldId', $fieldId1);
                $field2 = $customFields->getBy('fieldId', $fieldId2);
                if ($field1 && $field2) {
                    $value1 = floatval($field1->getValues()->first()->toArray()['value']);
                    $value2 = floatval($field2->getValues()->first()->toArray()['value']);
                    return ($value2 == 2 * $value1);
                } else
                    return false;
            } else
                return false;
        } else
            return false;
    }

}