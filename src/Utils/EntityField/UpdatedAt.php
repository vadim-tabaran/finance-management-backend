<?php

namespace App\Utils\EntityField;

use App\Annotation\Fetcher;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\HasLifecycleCallbacks()
 * @package App\Utils
 */
trait UpdatedAt
{
  /**
   * @var \DateTime
   * @ORM\Column(type="datetime", nullable=true)
   * @Fetcher()
   */
  private $updatedAt = null;

  /**
   * @return \DateTime
   */
  public function getUpdatedAt(): \DateTime
  {
    return $this->updatedAt;
  }

  /**
   * @param \DateTime $updatedAt
   */
  public function setUpdatedAt(\DateTime $updatedAt): void
  {
    $this->updatedAt = $updatedAt;
  }

  /**
   * @ORM\PreUpdate
   */
  public function setUpdatedAtValue()
  {
    $this->createdAt = new \DateTime();
  }
}
