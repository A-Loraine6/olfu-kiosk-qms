<?php

function queueTicketWindowEligibilitySql(string $ticketAlias = 't', bool $respectTicketWindow = true): string
{
    if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $ticketAlias)) {
        throw new InvalidArgumentException('Invalid ticket table alias.');
    }

    $assignmentCondition = $respectTicketWindow
        ? " AND ({$ticketAlias}.window_id IS NULL OR {$ticketAlias}.window_id = ?)"
        : '';

    return "
        EXISTS (
            SELECT 1
            FROM offices queue_eligibility_office
            WHERE queue_eligibility_office.id = {$ticketAlias}.office_id
              AND (
                  queue_eligibility_office.queue_mode <> 'service-based'
                  OR EXISTS (
                      SELECT 1 FROM transaction_types queue_service
                      WHERE queue_service.name = {$ticketAlias}.transaction_type
                        AND queue_service.office_id = {$ticketAlias}.office_id
                        AND (
                            NOT EXISTS (
                                SELECT 1 FROM service_windows queue_service_window
                                WHERE queue_service_window.service_id = queue_service.id
                                  AND queue_service_window.office_id = queue_service.office_id
                            )
                            OR EXISTS (
                                SELECT 1 FROM service_windows queue_service_window
                                WHERE queue_service_window.service_id = queue_service.id
                                  AND queue_service_window.office_id = queue_service.office_id
                                  AND queue_service_window.window_number = ?
                            )
                        )
                  )
              )
              AND (
                  queue_eligibility_office.queue_mode <> 'level-based'
                  OR EXISTS (
                      SELECT 1 FROM window_levels queue_window_level
                      WHERE queue_window_level.office_id = {$ticketAlias}.office_id
                        AND queue_window_level.window_number = ?
                        AND (
                            queue_window_level.student_level = 'both'
                            OR queue_window_level.student_level = {$ticketAlias}.student_level
                        )
                  )
              )
        )
        {$assignmentCondition}
    ";
}

function queueTicketServiceEligibilitySql(string $ticketAlias = 't'): string
{
    if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $ticketAlias)) {
        throw new InvalidArgumentException('Invalid ticket table alias.');
    }

    return "
        EXISTS (
            SELECT 1 FROM transaction_types queue_service
            WHERE queue_service.name = {$ticketAlias}.transaction_type
              AND queue_service.office_id = {$ticketAlias}.office_id
              AND (
                  NOT EXISTS (
                      SELECT 1 FROM service_windows queue_service_window
                      WHERE queue_service_window.service_id = queue_service.id
                        AND queue_service_window.office_id = queue_service.office_id
                  )
                  OR {$ticketAlias}.window_id IS NULL
                  OR EXISTS (
                      SELECT 1 FROM service_windows queue_service_window
                      WHERE queue_service_window.service_id = queue_service.id
                        AND queue_service_window.office_id = queue_service.office_id
                        AND queue_service_window.window_number = {$ticketAlias}.window_id
                  )
              )
        )
    ";
}

function queueTicketEligibleOnAnyWindowSql(string $ticketAlias, array $windowNumbers): string
{
    if (!$windowNumbers) {
        return '1 = 0';
    }

    $windowConditions = array_fill(
        0,
        count($windowNumbers),
        '(' . queueTicketWindowEligibilitySql($ticketAlias) . ')'
    );

    return '(' . implode(' OR ', $windowConditions) . ')';
}
