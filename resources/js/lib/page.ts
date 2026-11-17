export type Page<T> = Omit<App.Data.Support.PageData, 'data'> & { data: T[] };
