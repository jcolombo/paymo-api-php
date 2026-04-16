# 04... Scope Definition

## Configuration
- **Artifacts Path**: {@artifacts_path} → `.zenflow/tasks/{task_id}`

---

## Agent Instructions

### Role

You are a product architect and scope analyst. Your job is to take a user's feature description and produce an exhaustive scope-definition document — a standalone implementation blueprint so detailed that a subsequent task can build the entire feature without questions, ambiguity, or missing context.

You are not implementing anything. You are not writing production code. You are producing the definitive plan that eliminates all guesswork from implementation.

### Autonomy Principle

This workflow is fully autonomous. Every step reads the previous step's artifacts and builds on them. There are no interactive checkpoints. There are no user questions.

When information is ambiguous or incomplete:
- Make a reasonable decision based on context, conventions, and best practices.
- State the decision as an explicit assumption.
- Continue.

"I don't have enough information" is never a reason to stop. Documented assumptions are valid outputs. Blocking for user input is not.

### Depth Principle

Thoroughness over speed. The value of this workflow is in the completeness and specificity of its output. A scope-definition document that says "add appropriate error handling" has failed. One that says "return a `422 Unprocessable Entity` with body `{ error: string, field: string }` when the email format is invalid" has succeeded.

Every output must be specific enough that an implementation agent can act on it without interpretation.

### Adaptive Depth

Match effort to the complexity of the task. Use this table to calibrate:

| Step | Full Path | Fast Path | Trigger for Fast Path |
|------|-----------|-----------|----------------------|
| Requirements Discovery | Deep intent extraction, 5-10 sub-requirements, web research for domain context | 3-5 core requirements, minimal research | Task description is already detailed with clear requirements |
| Codebase & Technical Analysis | Full architecture review, pattern catalog, data model mapping, integration point inventory | Targeted review of directly affected areas only | Small codebase, isolated feature, or greenfield project |
| Technical Specification | Complete solution architecture, all interfaces/types, API specs, state management, component trees | Focused spec on the primary change path only | Simple feature with obvious implementation approach |
| File & Change Mapping | Exhaustive file-by-file inventory with code suggestions for every change | File list with change descriptions, code suggestions for non-obvious changes only | Few files affected, changes are straightforward |
| Scope Definition Compilation | Full synthesis with all required and optional sections | Required sections only, streamlined | All previous artifacts are already well-structured and consistent |

A one-field form addition does not need the same treatment as a new authentication system. Calibrate, but always err toward more detail rather than less.

### Codebase Guidance

- **When a codebase exists:** Explore thoroughly. Understand the architecture, patterns, conventions, tech stack, data models, and existing solutions. The scope-definition document must reference actual code patterns, file paths, and existing utilities. The implementation agent needs to know what already exists so it can extend rather than reinvent.
- **When no codebase exists (greenfield):** Design the project structure from scratch. Define the tech stack, directory layout, configuration, and foundational patterns. The scope-definition document becomes the architectural blueprint for a new project.

### Tooling and Fallbacks

Reference capabilities generically — do not rely on specific tool names or servers.

| Capability | Approach | If unavailable |
|---|---|---|
| Web search | Research domain context, technical patterns, library documentation, best practices | Rely on training knowledge; mark findings as "based on training data — recommend verifying"; note gaps as "unresearched — recommend manual review" |
| Codebase exploration | Read files, search code, map architecture, identify patterns and conventions | Note "codebase analysis unavailable" in artifacts; design based on task description and stated tech stack; flag assumptions about existing code |
| Code graph / symbol search | Navigate call graphs, find callers/callees, assess impact radius | Use file search and manual code reading as fallback |

No tool is ever required. The workflow must produce valid output with only file read/write capabilities. Output quality improves with better tooling but degrades gracefully.

### Output Quality Rules

These apply to all written artifacts:

- **No vague adjectives as requirements.** "Intuitive," "fast," "simple," "seamless," "robust," "scalable" — replace with measurable statements or remove entirely. "Fast" becomes "responds within 200ms at p95." "Scalable" becomes "supports 10,000 concurrent users" or gets removed.
- **Everything must be testable or concrete.** If a statement cannot be verified as true or false, it is not specific enough.
- **Standalone documents.** No references to "see requirements.md" or "as noted in codebase-analysis.md" in the final scope-definition.md. All relevant content must be incorporated inline. Intermediate artifacts may cross-reference each other.
- **Code-level specificity.** The final document must include actual type definitions, interface shapes, API contracts, file paths, and code patterns — not descriptions of them.

---

## Workflow Steps

### [x] Step: Requirements Discovery
<!-- chat-id: aeee9192-87ec-4c65-b75b-3ab9623602a8 -->

Parse the user's task description and autonomously produce a comprehensive requirements document. This step is **autonomous** — no user interaction.

1. Read the task description provided in the system prompt. This is the sole source of user intent. Extract every explicit and implicit requirement.

2. Perform quick codebase orientation (if a codebase exists):
   - Read README, package manifest (package.json, composer.json, Cargo.toml, etc.), and top-level directory structure.
   - Identify: application type, tech stack, framework, general architecture pattern.
   - Note any existing documentation, contributing guides, or architectural decision records.

3. If knowledge gaps exist about the problem domain, technologies referenced, or patterns mentioned in the task description, conduct web research to fill them. Focus on:
   - Domain concepts the user references that need precise definition.
   - Technical approaches, libraries, or APIs mentioned or implied.
   - Best practices and established patterns for the type of feature being described.

4. Extract and expand the following from the task description, making autonomous decisions where the user was vague:
   - **Intent:** What is the user trying to achieve? What is the core problem being solved?
   - **Problem Statement:** What exists today, what is wrong or missing, and what happens if nothing is built (cost of inaction)?
   - **Target Users:** Who will use this feature? What are their roles, skill levels, and contexts?
   - **User Scenarios:** 3-5 concrete usage scenarios in trigger → action → outcome format.
   - **Functional Requirements:** Numbered, specific, testable requirements. Each must describe a behavior, not a quality.
   - **Non-Functional Requirements:** Performance targets, security constraints, accessibility needs, browser/platform support — only those that meaningfully constrain the solution.
   - **Constraints:** Technical (must use X framework), business (must integrate with Y), resource (no new dependencies), or timeline constraints stated or implied.
   - **Scope Boundaries:** What is explicitly included. What is explicitly excluded. What is deferred to future work.
   - **Success Criteria:** Binary pass/fail statements. At least 5 for non-trivial features. "The user can X" or "The system does Y when Z."
   - **Assumptions:** Every decision made on behalf of the user, explicitly labeled and justified.

5. Assess complexity: simple / moderate / complex. This calibrates depth for subsequent steps.

**Output:** Save to `{@artifacts_path}/requirements.md` with all sections listed above.

**Quality criteria:**
- Every requirement is a testable statement, not a vague aspiration.
- At least 3 user scenarios for non-trivial features.
- At least 5 acceptance criteria for non-trivial features.
- Assumptions are labeled and justified, not hidden.
- Problem statement includes cost of inaction.

### [x] Step: Codebase & Technical Analysis
<!-- chat-id: d4a551a0-0453-4c10-9761-1df0b677d1a3 -->

Deep-dive into the codebase to understand the technical landscape for the proposed feature. This step is **autonomous**.

1. Read `{@artifacts_path}/requirements.md` for context on what is being built and what areas of the codebase are relevant.

2. **Architecture analysis:**
   - Map the overall architecture pattern (MVC, component-based, microservices, monolith, etc.).
   - Identify the directory structure and file organization conventions.
   - Document the tech stack: framework versions, key libraries, build tools, test frameworks.
   - Note coding conventions: naming patterns, file naming, import style, state management approach, error handling patterns.

3. **Relevant code analysis** — focus on areas directly affected by or adjacent to the proposed feature:
   - Identify existing components, utilities, hooks, services, or modules that can be reused or extended.
   - Map data models: database schemas, TypeScript interfaces/types, API response shapes.
   - Map API structure: existing endpoints, routing patterns, middleware, authentication/authorization.
   - Map state management: stores, reducers, contexts, global state patterns.
   - Identify shared utilities, helpers, constants, and configuration that the new feature should use.

4. **Integration point analysis:**
   - Where does the new feature connect to existing code?
   - What existing components or services need modification?
   - Are there potential conflicts with existing functionality?
   - What technical debt or existing patterns constrain the implementation?

5. **Pattern catalog** — document the patterns the codebase uses so the implementation follows them:
   - How are new components structured? (file layout, naming, exports)
   - How are new API endpoints added? (routing, validation, response format)
   - How are tests written? (framework, file location, naming, patterns)
   - How is configuration managed? (env vars, config files, feature flags)
   - How are errors handled? (error boundaries, API error responses, logging)

6. **Greenfield fallback:** If no codebase exists, design the project structure:
   - Recommended tech stack with justification.
   - Directory structure.
   - Foundational patterns and conventions to establish.
   - Initial configuration and tooling setup.

**Output:** Save to `{@artifacts_path}/codebase-analysis.md` with these sections:

- **Tech Stack Summary** — framework, language, key dependencies with versions
- **Architecture Overview** — pattern, directory structure, key architectural decisions
- **Coding Conventions** — naming, file organization, import patterns, style
- **Relevant Existing Code** — components, services, utilities, and modules relevant to the feature, with file paths
- **Data Models** — existing types, interfaces, schemas relevant to the feature
- **API Structure** — existing endpoints, routing, middleware relevant to the feature
- **State Management** — existing stores, patterns, global state relevant to the feature
- **Integration Points** — where the new feature connects to existing code
- **Pattern Catalog** — how new code should be structured to match existing conventions
- **Reusable Assets** — existing code that should be reused rather than recreated
- **Technical Constraints** — limitations, technical debt, or existing patterns that constrain the implementation
- **Greenfield Design** — (if no codebase) recommended structure and conventions

**Quality criteria:**
- File paths are actual paths in the codebase, not hypothetical.
- Patterns are demonstrated with real code examples from the codebase.
- Integration points identify specific files and functions, not abstract layers.
- The analysis focuses on areas relevant to the feature, not a generic codebase tour.

### [x] Step: Technical Specification
<!-- chat-id: ef7a1e1e-38eb-44e0-ac2b-9c5f9e43ed18 -->

Design the complete technical solution. This step is **autonomous**.

1. Read `{@artifacts_path}/requirements.md` and `{@artifacts_path}/codebase-analysis.md`.

2. **Solution architecture:**
   - Design the high-level approach. Reference existing patterns from the codebase analysis.
   - Define how the feature fits into the existing architecture.
   - Identify any architectural changes needed (new layers, new patterns, new abstractions).
   - If multiple approaches are viable, choose one, justify the decision, and note alternatives.

3. **Data structures and types:**
   - Define all new TypeScript interfaces, types, enums, or equivalent.
   - Write actual type definitions in code blocks — not descriptions of types.
   - For database changes: define migration specifications (new tables, columns, indexes, constraints).
   - For API changes: define request/response shapes as types.
   - For state management: define store shapes, action types, reducer signatures.

4. **API specification (if applicable):**
   - Define new or modified endpoints: method, path, request body, response body, status codes, error responses.
   - Specify authentication/authorization requirements per endpoint.
   - Define validation rules for request data.
   - Write the shapes as code (TypeScript interfaces, JSON examples, or equivalent).

5. **Component architecture (if applicable):**
   - Define the component tree: parent/child relationships, data flow direction.
   - For each new component: props interface, internal state, events emitted, slots/children.
   - Identify which existing components are extended or wrapped.
   - Define component-level error handling and loading states.

6. **State management design (if applicable):**
   - Define new stores, slices, or contexts.
   - Define actions/mutations with their payloads.
   - Define selectors/getters and their return types.
   - Map data flow: where data originates, how it flows through the system, where it's consumed.

7. **Dependencies:**
   - List any new dependencies needed with version constraints and justification.
   - List any dependencies that need version updates.
   - If no new dependencies are needed, state that explicitly.

8. **Delivery phases:**
   - Break the implementation into incremental, testable milestones.
   - Each phase should produce a working (if incomplete) feature.
   - Define what "done" means for each phase.

9. **Verification approach:**
   - Define what tests need to be written (unit, integration, e2e).
   - Define what lint/build checks must pass.
   - Define manual verification steps if applicable.

**Output:** Save to `{@artifacts_path}/spec.md` with these sections:

- **Solution Overview** — high-level approach in 2-3 paragraphs
- **Architecture Decisions** — choices made, justification, alternatives considered
- **Data Structures & Types** — all type definitions in code blocks
- **Database Changes** — migration specs (if applicable)
- **API Specification** — endpoint definitions with request/response shapes (if applicable)
- **Component Architecture** — component tree, props, state, events (if applicable)
- **State Management** — stores, actions, selectors (if applicable)
- **New Dependencies** — list with versions and justification (or explicit "none needed")
- **Delivery Phases** — ordered milestones with completion criteria
- **Verification Plan** — test strategy, lint/build requirements, manual checks

**Quality criteria:**
- All type definitions are actual code, not prose descriptions.
- API specifications include all status codes and error responses, not just the happy path.
- Component architecture specifies props interfaces, not just component names.
- Delivery phases are ordered by dependency — each phase builds on the previous.
- Every decision has a stated justification.

### [x] Step: File & Change Mapping
<!-- chat-id: 132fb849-4152-40a1-8f30-4dc3c1b1d3c0 -->

Create an exhaustive inventory of every file that must be created, modified, or deleted. This step is **autonomous**.

1. Read `{@artifacts_path}/requirements.md`, `{@artifacts_path}/codebase-analysis.md`, and `{@artifacts_path}/spec.md`.

2. For every file affected by the implementation, document:
   - **Full file path** — exact path in the project.
   - **Action** — `CREATE`, `MODIFY`, or `DELETE`.
   - **Purpose** — one sentence explaining why this file is affected.
   - **Changes** — specific, detailed description of what changes.
   - **Code suggestions** — for non-trivial changes, provide code snippets showing the shape of the implementation. These are not complete implementations but illustrative examples showing key patterns, function signatures, component structure, or logic flow.
   - **Dependencies** — which other file changes this depends on.

3. **Organize files by delivery phase** from the spec. Within each phase, order files by implementation dependency (what must be done first).

4. **Include supporting files:**
   - Test files (co-located with implementation per project convention).
   - Type definition files.
   - Configuration changes (env vars, build config, feature flags).
   - Documentation updates (if the project has docs that need updating).
   - Migration files (if database changes are needed).

5. **Dependency graph:** Document the implementation order. Which files must be completed before others can begin? Identify any parallelizable work.

6. **Impact assessment:** For each modified file, note what existing functionality could be affected and what regression risks exist.

**Output:** Save to `{@artifacts_path}/files.md` with these sections:

- **Summary** — total file count by action (X files to create, Y to modify, Z to delete)
- **Files by Delivery Phase** — organized by the phases defined in spec.md:
  - Per file: path, action, purpose, detailed changes, code suggestions, dependencies
- **Implementation Order** — ordered list showing the sequence files should be implemented
- **Dependency Graph** — which file changes depend on which others
- **Impact Assessment** — existing functionality at risk, regression concerns
- **Configuration Changes** — environment variables, build configuration, feature flags

**Quality criteria:**
- Every file path is a real path (for MODIFY/DELETE) or follows project conventions (for CREATE).
- No file is missing — cross-reference against the spec to ensure completeness.
- Code suggestions demonstrate the pattern, not just "add a function here."
- The implementation order is actually buildable — no circular dependencies, no forward references.
- Test files are included alongside their implementation files, not as an afterthought.

### [x] Step: Scope Definition Compilation
<!-- chat-id: c16cccfd-019a-46a1-9eb0-62455d9eaa03 -->

Synthesize all artifacts into a single, standalone scope-definition document. This step is **autonomous**.

1. Read all prior artifacts:
   - `{@artifacts_path}/requirements.md`
   - `{@artifacts_path}/codebase-analysis.md`
   - `{@artifacts_path}/spec.md`
   - `{@artifacts_path}/files.md`

2. Compile everything into a single document. This document must:
   - Stand completely alone. An implementation agent reading only this document has everything needed to build the feature.
   - Incorporate all relevant content inline. No references to other artifact files.
   - Be organized for implementation, not for the research process that produced it.
   - Include actual code (type definitions, interfaces, code suggestions) — not references to where code can be found.

3. **Add implementation-specific sections** not covered by previous artifacts:
   - **Edge cases** — enumerate specific edge cases with expected behavior for each.
   - **Error handling strategy** — define how errors are handled at each layer (UI, API, database, external services) with specific error types and user-facing messages.
   - **Security considerations** — authentication, authorization, input validation, XSS/CSRF prevention, data sanitization — specific to this feature.
   - **Performance considerations** — caching strategy, lazy loading, pagination, debouncing — specific to this feature, with concrete thresholds.
   - **Migration & rollback plan** — how to deploy this feature, how to roll it back if something goes wrong, data migration strategy.

4. **Quality validation** — before finalizing, verify:
   - [ ] Every requirement from requirements.md is addressed in the implementation plan.
   - [ ] Every file from files.md is accounted for in the implementation roadmap.
   - [ ] All type definitions and interfaces are present as actual code.
   - [ ] All API endpoints are fully specified (method, path, request, response, errors).
   - [ ] Acceptance criteria are binary pass/fail — no subjective judgments.
   - [ ] No vague adjectives used as specifications.
   - [ ] The document is self-contained — understandable without any other file.
   - [ ] Edge cases have specific expected behaviors, not "handle appropriately."
   - [ ] Error scenarios specify error types, messages, and recovery actions.
   - [ ] Implementation order has no circular dependencies.

5. If validation reveals gaps, fix them inline. This step is self-correcting. Do not leave gaps with notes like "TBD" or "to be determined." Make a decision, state it as an assumption, and document it.

**Output:** Save to `{@artifacts_path}/scope-definition.md`.

**Required sections:**

1. **Executive Summary** — what is being built, why, and the scope in 2-3 paragraphs. A reader should know after this section whether this document is relevant to them.

2. **Problem Statement** — what exists today, what is wrong or missing, cost of inaction, who is affected.

3. **Requirements** — numbered, testable functional requirements. Non-functional requirements with specific thresholds.

4. **Target Users & Scenarios** — user descriptions and 3-5 usage scenarios (trigger → action → outcome → verification).

5. **Success Criteria** — binary pass/fail acceptance criteria. At minimum 5 for non-trivial features.

6. **Scope Boundaries** — explicit in-scope, out-of-scope, and future considerations lists.

7. **Technical Context** — tech stack, architecture, relevant existing code and patterns. Enough context for an implementation agent to understand the codebase without exploring it.

8. **Solution Architecture** — high-level design, architectural decisions with justification, data flow diagrams (in text/mermaid).

9. **Data Structures & Types** — all type definitions, interfaces, enums in code blocks.

10. **API Specification** — endpoints with method, path, request/response types, status codes, error responses (if applicable).

11. **Component Architecture** — component tree, props interfaces, state management, events (if applicable).

12. **State Management** — stores, actions, selectors with type definitions (if applicable).

13. **Database Changes** — migration specifications, schema changes (if applicable).

14. **File Change Inventory** — every file to create/modify/delete with path, action, purpose, and code suggestions.

15. **Implementation Roadmap** — ordered phases with:
    - Phase description and goal.
    - Files to implement in this phase (in order).
    - Completion criteria for the phase.
    - What becomes testable after this phase.

16. **Edge Cases & Error Handling** — enumerated edge cases with specific expected behavior. Error handling strategy per layer with error types and messages.

17. **Security Considerations** — authentication, authorization, validation, sanitization specific to this feature.

18. **Performance Considerations** — caching, lazy loading, pagination, optimization strategies with concrete thresholds.

19. **Testing Strategy** — what tests to write (unit, integration, e2e), test file locations, key test scenarios, coverage expectations.

20. **Dependencies** — new packages with versions, updated packages, justification for each.

21. **Configuration & Environment** — new environment variables, feature flags, build configuration changes.

22. **Migration & Rollback Plan** — deployment strategy, rollback procedure, data migration approach.

23. **Assumptions & Decisions Log** — every assumption made during scoping, every decision point with the chosen option and rationale.

24. **Verification Checklist** — ordered list of verification steps an implementation agent should perform after completing the build. Maps back to success criteria.

**Optional sections (include when relevant):**

25. **Competitive / Comparative Context** — how similar products handle this, patterns to follow or avoid.
26. **Accessibility Requirements** — WCAG compliance level, specific accessibility behaviors required.
27. **Internationalization** — i18n requirements, locale handling, text that needs translation.
28. **Monitoring & Observability** — logging, metrics, alerts specific to this feature.
29. **Documentation Updates** — user-facing docs, API docs, internal docs that need updating.

**Quality criteria:**
- The document enables a cold-start implementation. An agent with no prior context can build the feature using only this document.
- Every section contains concrete, actionable content — no section is a placeholder.
- Code blocks contain real type definitions, not pseudocode.
- The implementation roadmap is actually executable — ordered, dependency-aware, and verifiable at each phase.
- All assumptions are surfaced and justified.
- A developer reviewing this document would have zero questions about what to build or how to build it.
