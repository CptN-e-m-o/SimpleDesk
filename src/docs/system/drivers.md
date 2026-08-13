# System drivers

Connection = how SimpleDesk obtains access to an infrastructure resource.

Driver = how a specific SimpleDesk subsystem uses that resource.

The Drivers page currently presents Queue, Cache, Broadcasting, Search, and Storage categories without activating or changing any runtime driver. Existing `QUEUE_CONNECTION` and `CACHE_STORE` values remain the source of truth.

Each category will have its own registry and contract because queue execution, caching, broadcasting, search, and storage have different lifecycle and configuration requirements. They will not be combined into a universal `DriverContract` or a generic JSON settings store.
