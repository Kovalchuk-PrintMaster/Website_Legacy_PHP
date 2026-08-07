# Project coordination

The `coordination/` directory contains project-governance and coordination
evidence. It is not part of the website runtime or deployment payload.

## Blueprint coordination

Blueprint is an external project-coordination module used for higher-level
planning, progress review, stage transitions and work coordination.

Day-to-day implementation may proceed without active Blueprint interaction.
When a work stage reaches a suitable checkpoint, project reports and relevant
evidence may be provided through this coordination layer.

## Reports

`coordination/reports/` contains versioned project evidence, including:

- implementation and completion reports;
- audit results;
- incident and recovery records;
- architecture/ownership findings;
- stage/checkpoint summaries;
- reports intended for Blueprint or other project-level coordination.

Reports document project state and decisions. They do not contain runtime
secrets, credentials or private communication content.

## Temporary state

Local scratch files such as root `tmp.py`, root `tmp.php`, and the `tmp/`
directory are temporary operator/development state and are not canonical
repository content.
