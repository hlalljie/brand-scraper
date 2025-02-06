import { React, useState, use } from "react";

const Home = () => {
    const [input, setInput] = useState("");
    const [loading, setLoading] = useState(false);
    const [resData, setResData]: [Object | null, any] = useState(null);

    const handleSubmit = () => {
        // const fetchAddress = "/api/brand_scraper";
        const fetchAddress = "api/test";

        setLoading(true);
        fetch(fetchAddress, {
            method: "Post",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({ url: input }),
        })
            .then((res) => res.json())
            .then((data) => {
                setResData(data);
                setLoading(false);
            });
    };

    return (
        <>
            <div>
                <h1>Home</h1>
            </div>
            <input
                type="text"
                onChange={(e) => setInput(e.target.value)}
            ></input>
            <button onClick={handleSubmit}>Submit</button>
            {loading && <Loading />}
            {resData ? <ResultsDisplay resData={resData} /> : null}
        </>
    );
};

const Loading = () => {
    return <div className="loadingResults">Loading</div>;
};

const ResultsDisplay = ({ resData }) => {
    return (
        <div className="resultsDisplay">
            {resData.error ? (
                <ErrorDisplay error={resData.error} />
            ) : resData.brandData ? (
                <DataDisplay resData={resData} />
            ) : (
                "No error and no data?!?"
            )}
        </div>
    );
};

const ErrorDisplay = ({ error }) => {
    return <div className="errorDisplay">{error}</div>;
};

const DataDisplay = ({ resData }) => {
    return (
        <div className="dataDisplay">
            {resData.brandData.colors ? (
                <ColorDisplay colors={resData.brandData.colors} />
            ) : null}
            {resData.brandData.fonts ? (
                <FontDisplay fonts={resData.brandData.fonts} />
            ) : null}
            <ParsedDataDisplay parsedData={resData.parsedData} />
        </div>
    );
};

const ColorDisplay = ({ colors }) => {
    return (
        <div className="colorDisplay">
            <h3>Colors</h3>
            <p>{JSON.stringify(colors, null, 2)}</p>
        </div>
    );
};

const FontDisplay = ({ fonts }) => {
    return (
        <div className="fontDisplay">
            <h3>Fonts</h3>
            <p>{JSON.stringify(fonts, null, 2)}</p>
        </div>
    );
};

const ParsedDataDisplay = ({ parsedData }) => {
    return (
        <div className="fullDataDisplay">
            <h3>Full Data</h3>
            <p>{parsedData}</p>
        </div>
    );
};

export default Home;
