# Signal Plugin - Architecture V2 Proposal (Brainstorming)

## Problem Statement
The current database structure is designed for a **Linear Pipeline** (Trigger -> Filter -> Action A -> Action B).
- Filters are stored as a JSON blob on the Trigger.
- Actions are stored in a separate table but linked sequentially.
- Flow metadata (visual position) is split between tables.

## Goals for V2
1.  **Branching Logic:** Support "If/Else" and "Switch" paths (e.g., If Category=A -> Email, If Category=B -> Slack).
2.  **Scalability:** Allow third-party plugins to register new Node Types easily.
3.  **Scheduled Triggers:** Support Cron/Time-based triggers (e.g., "Every Friday at 5 PM").
4.  **Unified Flow:** The database should reflect the graph structure (Nodes & Edges).

## Proposed Schema Changes

### 1. `signal_workflows` (Renamed from `signal_triggers`)
Container for the entire automation.
- `id`
- `name`
- `status` (active/draft)
- `description`
- `created_at`, `updated_at`

### 2. `signal_nodes`
Generic table for ALL blocks (Triggers, Filters, Actions, Logic).
- `id`
- `workflow_id` (FK)
- `type` (string: 'trigger', 'action', 'condition', 'delay', 'iterator')
- `class_type` (string: e.g., 'Base33\Signal\Nodes\WebhookAction')
- `name` (string: Display label)
- `configuration` (JSON: Stores URL for webhooks, Operators for filters, Schedule for cron)
- `position` (JSON: {x, y} for Flow Editor)
- `output_ports` (JSON: defines available exits, e.g., ['success', 'fail'] or ['true', 'false'])

### 3. `signal_edges` (New Table)
Defines the path between nodes.
- `id`
- `workflow_id` (FK)
- `source_node_id` (FK)
- `source_handle` (string: e.g., 'true', 'false', 'default')
- `target_node_id` (FK)

## Migration Strategy
1.  **Event Triggers:** Become a `SignalNode` of type `trigger` (subtype: `event`).
2.  **Scheduled Triggers:** Become a `SignalNode` of type `trigger` (subtype: `cron`).
3.  **Filters:** Become a `SignalNode` of type `condition` (Branching).
    - Output 'True' -> Next Node
    - Output 'False' -> Stop or Alternative Node
4.  **Actions:** Become `SignalNode` of type `action`.

## New Logic Examples
- **Multi-path:** Connect Filter 'True' handle to Email Action, 'False' handle to Log Action.
- **Timed:** Start flow with a "Cron Node" configured for `0 0 * * *`.

## Next Steps
- Create migration for `signal_nodes` and `signal_edges`.
- Refactor `FlowEditor` to handle generic nodes and custom edges.
- Update `SignalEngine` to traverse the graph instead of a linear list.
