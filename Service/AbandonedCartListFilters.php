<?php

declare(strict_types=1);

namespace AbandonedCartReminder\Service;

use Symfony\Component\HttpFoundation\Request;

final readonly class AbandonedCartListFilters
{
    public const SORTABLE_FIELDS = [
        'customer' => 'Email',
        'cart' => 'CartId',
        'state' => 'Status',
        'reminders' => 'RemindersSent',
        'last_reminder' => 'LastReminderAt',
        'followed_since' => 'CreatedAt',
    ];

    private const DIRECTIONS = ['asc', 'desc'];
    private const DEFAULT_FIELD = 'followed_since';
    private const DEFAULT_DIRECTION = 'desc';

    public function __construct(
        public string $search = '',
        public string $sortField = self::DEFAULT_FIELD,
        public string $direction = self::DEFAULT_DIRECTION,
        public int $page = 1,
    ) {
    }

    public static function fromRequest(?Request $request): self
    {
        if (null === $request) {
            return new self();
        }

        $sortField = (string) $request->query->get('order', self::DEFAULT_FIELD);
        $direction = strtolower((string) $request->query->get('direction', self::DEFAULT_DIRECTION));

        return new self(
            trim((string) $request->query->get('search', '')),
            \array_key_exists($sortField, self::SORTABLE_FIELDS) ? $sortField : self::DEFAULT_FIELD,
            \in_array($direction, self::DIRECTIONS, true) ? $direction : self::DEFAULT_DIRECTION,
            max(1, (int) $request->query->get('page', 1)),
        );
    }

    public function propelColumn(): string
    {
        return self::SORTABLE_FIELDS[$this->sortField];
    }

    public function isDescending(): bool
    {
        return 'desc' === $this->direction;
    }

    /**
     * The query parameters a link must carry to keep this view, minus the page.
     *
     * @return array<string, string>
     */
    public function asLinkParameters(): array
    {
        $parameters = ['order' => $this->sortField, 'direction' => $this->direction];

        if ('' !== $this->search) {
            $parameters['search'] = $this->search;
        }

        return $parameters;
    }

    public function directionForColumn(string $field): string
    {
        if ($field !== $this->sortField) {
            return 'asc';
        }

        return $this->isDescending() ? 'asc' : 'desc';
    }
}
