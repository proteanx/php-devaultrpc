<?php

declare(strict_types=1);

namespace Protean\DeVault\Responses;

use Protean\DeVault\Traits\Collection;
use Protean\DeVault\Traits\ImmutableArray;
use Protean\DeVault\Traits\SerializableContainer;

class DeVaultdResponse extends Response implements
    \ArrayAccess,
    \Countable,
    \Serializable,
    \JsonSerializable
{
    use Collection, ImmutableArray, SerializableContainer;

    /**
     * Gets array representation of response object.
     *
     * @return array
     */
    public function toArray() : array
    {
        return (array) $this->result();
    }

    /**
     * Gets root container of response object.
     *
     * @return array
     */
    public function toContainer() : array
    {
        return $this->container;
    }
}
